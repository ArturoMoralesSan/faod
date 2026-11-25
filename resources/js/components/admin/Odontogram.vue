<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Trash2 } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import FormDialog from '@/components/admin/FormDialog.vue';
import FormInput from '@/components/admin/FormInput.vue';
import FormSelect from '@/components/admin/FormSelect.vue';
import { confirmDelete, enumOptions, formatDateTime, label } from '@/lib/admin';
import admin from '@/routes/admin';
import type { OdontogramEntryData, SelectOption } from '@/types/admin';

/**
 * Odontograma FDI. El estado de cada diente es el registro más reciente
 * del diente completo y de cada una de sus caras.
 */
const props = defineProps<{
    patientId: number;
    entries: OdontogramEntryData[];
    config: {
        surfaces: string[];
        surfaceConditions: string[];
        toothConditions: string[];
        permanentTeeth: number[];
        primaryTeeth: number[];
    };
    diagnosisOptions: SelectOption[];
    canEdit: boolean;
}>();

const showPrimary = ref(false);

/* ------------------------------- Estado actual ------------------------------- */

interface ToothState {
    whole: string | null;
    surfaces: Record<string, string>;
}

const state = computed<Record<string, ToothState>>(() => {
    const result: Record<string, ToothState> = {};

    // Las entradas llegan de la más reciente a la más antigua: gana la primera.
    for (const entry of props.entries) {
        const tooth = (result[entry.tooth] ??= { whole: null, surfaces: {} });

        if (entry.surface === null) {
            tooth.whole ??= entry.condition;
        } else {
            tooth.surfaces[entry.surface] ??= entry.condition;
        }
    }

    return result;
});

const rows = computed(() => {
    const split = (teeth: number[], half: number) => [teeth.slice(0, half), teeth.slice(half)];
    const permanent = props.config.permanentTeeth;
    const primary = props.config.primaryTeeth;

    return showPrimary.value
        ? [split(primary.slice(0, 10), 5), split(primary.slice(10), 5)]
        : [split(permanent.slice(0, 16), 8), split(permanent.slice(16), 8)];
});

/* --------------------------- Geometría de cada cara --------------------------- */

const POLYGONS = {
    top: '0,0 40,0 28,12 12,12',
    bottom: '0,40 40,40 28,28 12,28',
    left: '0,0 12,12 12,28 0,40',
    right: '40,0 28,12 28,28 40,40',
    center: '12,12 28,12 28,28 12,28',
} as const;

type Region = keyof typeof POLYGONS;

/**
 * Cara que corresponde a cada región según el cuadrante:
 * superiores con vestibular arriba; inferiores con vestibular abajo;
 * mesial hacia la línea media.
 */
const surfaceAt = (tooth: number, region: Region): string => {
    const quadrant = Math.floor(tooth / 10);
    const upper = [1, 2, 5, 6].includes(quadrant);
    const rightSide = [1, 4, 5, 8].includes(quadrant); // lado derecho del paciente = izquierda en pantalla

    return {
        center: 'O',
        top: upper ? 'V' : 'L',
        bottom: upper ? 'L' : 'V',
        left: rightSide ? 'D' : 'M',
        right: rightSide ? 'M' : 'D',
    }[region];
};

const COLORS: Record<string, string> = {
    caries: '#DC2626',
    obturado: '#2563EB',
    sellador: '#16A34A',
    fractura: '#EA580C',
};

const WHOLE_MARKS: Record<string, { text: string; color: string }> = {
    ausente: { text: 'AUS', color: '#858B93' },
    extraccion_indicada: { text: 'EXT', color: '#DC2626' },
    corona: { text: 'COR', color: '#2563EB' },
    endodoncia: { text: 'ENDO', color: '#7C3AED' },
    implante: { text: 'IMP', color: '#0891B2' },
    protesis: { text: 'PROT', color: '#2563EB' },
    fractura: { text: 'FX', color: '#EA580C' },
};

const fill = (tooth: number, region: Region) => {
    const condition = state.value[String(tooth)]?.surfaces[surfaceAt(tooth, region)];

    return condition ? (COLORS[condition] ?? '#FFFFFF') : '#FFFFFF';
};

const wholeMark = (tooth: number) => {
    const whole = state.value[String(tooth)]?.whole;

    return whole ? WHOLE_MARKS[whole] : undefined;
};

const legend = computed<{ condition: string; color: string; kind: 'surface' | 'whole'; text?: string }[]>(() => [
    ...Object.entries(COLORS).map(([condition, color]) => ({ condition, color, kind: 'surface' as const })),
    ...Object.entries(WHOLE_MARKS)
        .filter(([condition]) => condition !== 'fractura')
        .map(([condition, mark]) => ({ condition, color: mark.color, kind: 'whole' as const, text: mark.text })),
]);

/* ---------------------------------- Captura ---------------------------------- */

const dialogOpen = ref(false);
const selectedTooth = ref<number | null>(null);

const form = useForm({
    tooth: '',
    whole: '',
    surfaces: {} as Record<string, string>,
    patient_diagnosis_id: null as number | null,
    notes: '',
});

// Errores de los campos anidados (entries.N.condition) que no están en el tipo del formulario.
const errors = computed(() => form.errors as Record<string, string | undefined>);

const history = computed(() => props.entries.filter((e) => e.tooth === String(selectedTooth.value)));

const SURFACE_NAMES: Record<string, string> = {
    O: 'Oclusal / incisal',
    M: 'Mesial',
    D: 'Distal',
    V: 'Vestibular',
    L: 'Lingual / palatina',
};

const openTooth = (tooth: number): void => {
    selectedTooth.value = tooth;
    form.reset();
    form.clearErrors();
    form.tooth = String(tooth);
    form.surfaces = Object.fromEntries(props.config.surfaces.map((s) => [s, '']));
    dialogOpen.value = true;
};

const hasChanges = computed(() => form.whole !== '' || Object.values(form.surfaces).some((v) => v !== ''));

const submit = (): void => {
    form.transform((data) => ({
        tooth: data.tooth,
        patient_diagnosis_id: data.patient_diagnosis_id,
        notes: data.notes || null,
        entries: [
            ...(data.whole ? [{ surface: null, condition: data.whole }] : []),
            ...Object.entries(data.surfaces)
                .filter(([, condition]) => condition)
                .map(([surface, condition]) => ({ surface, condition })),
        ],
    })).post(admin.patients.odontogram.store(props.patientId).url, {
        preserveScroll: true,
        onSuccess: () => (dialogOpen.value = false),
    });
};

const removeEntry = (id: number): void => {
    confirmDelete(
        '¿Eliminar registro del odontograma?',
        'Úsalo solo para corregir una captura equivocada.',
        admin.patients.odontogram.destroy({ patient: props.patientId, entry: id }).url,
        { preserveState: true },
    );
};
</script>

<template>
    <div>
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div class="inline-flex rounded-xl border border-[#E3E5E8] bg-white p-1 text-xs font-semibold">
                <button
                    type="button"
                    :class="['rounded-lg px-3 py-1.5', !showPrimary ? 'bg-[#941B2B] text-white' : 'text-[#525960]']"
                    @click="showPrimary = false"
                >
                    Permanentes
                </button>
                <button
                    type="button"
                    :class="['rounded-lg px-3 py-1.5', showPrimary ? 'bg-[#941B2B] text-white' : 'text-[#525960]']"
                    @click="showPrimary = true"
                >
                    Temporales
                </button>
            </div>
            <p v-if="canEdit" class="text-xs text-[#858B93]">Haz clic en un diente para registrar su estado.</p>
        </div>

        <div class="overflow-x-auto">
            <div class="mx-auto flex w-max flex-col gap-4">
                <div v-for="(row, rowIndex) in rows" :key="rowIndex" class="flex gap-4">
                    <div
                        v-for="(half, halfIndex) in row"
                        :key="halfIndex"
                        :class="['flex gap-1', halfIndex === 0 ? 'border-r-2 border-[#E3E5E8] pr-4' : '']"
                    >
                        <button
                            v-for="tooth in half"
                            :key="tooth"
                            type="button"
                            :disabled="!canEdit && !entries.some((e) => e.tooth === String(tooth))"
                            :class="['flex flex-col items-center gap-1 rounded-lg p-1 transition', rowIndex === 1 ? 'flex-col-reverse' : '', 'hover:bg-[#F1F2F4]']"
                            :title="`Diente ${tooth}`"
                            @click="openTooth(tooth)"
                        >
                            <span class="text-[11px] font-semibold text-[#525960]">{{ tooth }}</span>
                            <span class="relative block h-9 w-9">
                                <svg viewBox="0 0 40 40" class="h-9 w-9">
                                    <polygon
                                        v-for="(points, region) in POLYGONS"
                                        :key="region"
                                        :points="points"
                                        :fill="fill(tooth, region)"
                                        stroke="#858B93"
                                        stroke-width="1"
                                    />
                                    <template v-if="wholeMark(tooth)">
                                        <line v-if="['ausente', 'extraccion_indicada'].includes(state[String(tooth)]?.whole ?? '')" x1="2" y1="2" x2="38" y2="38" :stroke="wholeMark(tooth)!.color" stroke-width="3" />
                                        <line v-if="['ausente', 'extraccion_indicada'].includes(state[String(tooth)]?.whole ?? '')" x1="38" y1="2" x2="2" y2="38" :stroke="wholeMark(tooth)!.color" stroke-width="3" />
                                        <circle v-if="['corona', 'protesis', 'implante'].includes(state[String(tooth)]?.whole ?? '')" cx="20" cy="20" r="18" fill="none" :stroke="wholeMark(tooth)!.color" stroke-width="3" />
                                    </template>
                                </svg>
                            </span>
                            <span class="h-3 text-[9px] font-bold leading-3" :style="{ color: wholeMark(tooth)?.color }">{{ wholeMark(tooth)?.text }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Leyenda -->
        <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs text-[#525960]">
            <span v-for="item in legend" :key="item.condition" class="inline-flex items-center gap-1.5">
                <span v-if="item.kind === 'surface'" class="h-3 w-3 rounded-sm border border-[#858B93]" :style="{ backgroundColor: item.color }" />
                <span v-else class="text-[10px] font-bold" :style="{ color: item.color }">{{ item.text }}</span>
                {{ label(item.condition) }}
            </span>
        </div>

        <!-- Diálogo del diente -->
        <FormDialog
            v-model:open="dialogOpen"
            :title="`Diente ${selectedTooth}`"
            description="Registra solo lo que cambió; lo demás conserva su estado anterior."
            :loading="form.processing"
            @submit="canEdit && hasChanges ? submit() : (dialogOpen = false)"
        >
            <template v-if="canEdit">
                <div class="md:col-span-2">
                    <FormSelect
                        id="odo_whole"
                        v-model="form.whole"
                        label="Diente completo"
                        placeholder="Sin cambio"
                        :options="enumOptions(config.toothConditions)"
                        :error="errors['entries.0.condition']"
                    />
                </div>

                <FormSelect
                    v-for="surface in config.surfaces"
                    :id="`odo_${surface}`"
                    :key="surface"
                    v-model="form.surfaces[surface]"
                    :label="`${surface} · ${SURFACE_NAMES[surface]}`"
                    placeholder="Sin cambio"
                    :options="enumOptions(config.surfaceConditions)"
                />

                <FormSelect
                    id="odo_diag"
                    v-model="form.patient_diagnosis_id"
                    label="Diagnóstico relacionado"
                    placeholder="Ninguno"
                    :options="diagnosisOptions"
                    nullable
                />

                <div class="md:col-span-2">
                    <FormInput id="odo_notes" v-model="form.notes" label="Nota" :error="form.errors.notes" />
                </div>
                <p v-if="errors.entries" class="form-error md:col-span-2">{{ errors.entries }}</p>
            </template>

            <div class="md:col-span-2">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-[#858B93]">Historial del diente</p>
                <ul v-if="history.length" class="max-h-56 divide-y divide-[#E3E5E8] overflow-y-auto rounded-xl border border-[#E3E5E8]">
                    <li v-for="entry in history" :key="entry.id" class="flex items-center justify-between gap-2 px-3 py-2">
                        <div class="min-w-0 text-sm">
                            <p class="font-semibold text-[#292D32]">
                                {{ entry.surface ? `${entry.surface} · ` : 'Diente completo · ' }}{{ label(entry.condition) }}
                            </p>
                            <p class="text-xs text-[#858B93]">
                                {{ formatDateTime(entry.recorded_at) }}<template v-if="entry.creator"> · {{ entry.creator.name }}</template><template v-if="entry.notes"> · {{ entry.notes }}</template>
                            </p>
                        </div>
                        <button
                            v-if="canEdit"
                            type="button"
                            class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-[#941B2B] hover:bg-[#F1F2F4]"
                            title="Eliminar registro"
                            @click="removeEntry(entry.id)"
                        >
                            <Trash2 class="h-4 w-4" />
                        </button>
                    </li>
                </ul>
                <p v-else class="text-sm italic text-[#858B93]">Sin registros para este diente.</p>
            </div>
        </FormDialog>
    </div>
</template>
