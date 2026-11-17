<script setup lang="ts">
import { computed } from 'vue';

import { label as labelFor } from '@/lib/admin';

const props = withDefaults(
    defineProps<{
        value?: string | boolean | null;
        label?: string;
    }>(),
    {
        value: null,
        label: '',
    },
);

// Verde = activo/positivo, gris = neutro, rojo = baja/negativo, ámbar = pendiente.
const tone = computed(() => {
    const value = props.value;

    const positive = ['activo', 'active', 'inscrito', 'en_curso', 'egresado', 'operativo', 'concluida', 'asistio', 'resuelta', 'cerrada'];
    const negative = ['baja', 'inactive', 'no_inscrito', 'fuera_servicio', 'cancelada', 'falta', 'abierta'];
    const pending = ['pendiente', 'planeada', 'programada', 'mantenimiento', 'en_proceso', 'justificada'];

    if (value === true || positive.includes(String(value))) {
        return 'border-[#BFE3CC] bg-[#E2F3E8] text-[#187744]';
    }

    if (value === false || negative.includes(String(value))) {
        return 'border-[#E8C7CC] bg-[#F9ECEE] text-[#941B2B]';
    }

    if (pending.includes(String(value))) {
        return 'border-[#F1DDB0] bg-[#FDF6E7] text-[#8A5A00]';
    }

    return 'border-[#E3E5E8] bg-[#F7F8FA] text-[#525960]';
});

const text = computed(() => {
    if (props.label) {
        return props.label;
    }

    if (typeof props.value === 'boolean') {
        return props.value ? 'Activo' : 'Inactivo';
    }

    return labelFor(props.value);
});
</script>

<template>
    <span
        :class="[
            'inline-flex items-center whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold',
            tone,
        ]"
    >
        {{ text }}
    </span>
</template>
