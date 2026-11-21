<script setup lang="ts">
import { formatDateTime, formatMoney, formatQty, fullName, label } from '@/lib/admin';
import type { MovementRow } from '@/types/admin';

/**
 * Tabla de movimientos del kárdex.
 */
defineProps<{
    movements: MovementRow[];
    showItem?: boolean;
    unit?: string;
}>();
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-[#F7F8FA] text-xs font-semibold uppercase tracking-wide text-[#383D43]">
                <tr>
                    <th class="px-4 py-3">Fecha</th>
                    <th v-if="showItem" class="px-4 py-3">Artículo</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Almacén / lote</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                    <th class="px-4 py-3 text-right">Saldo</th>
                    <th class="px-4 py-3">Detalle</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E3E5E8]">
                <tr v-for="m in movements" :key="m.id">
                    <td class="whitespace-nowrap px-4 py-3 text-[#525960]">{{ formatDateTime(m.moved_at) }}</td>
                    <td v-if="showItem" class="px-4 py-3">
                        <p class="font-semibold text-[#292D32]">{{ m.item?.name }}</p>
                        <p class="text-xs text-[#858B93]">{{ m.item?.code }}</p>
                    </td>
                    <td class="px-4 py-3 text-[#525960]">{{ label(m.type) }}</td>
                    <td class="px-4 py-3 text-[#525960]">
                        {{ m.warehouse.code }}
                        <p v-if="m.lot?.lot_number || m.lot?.expires_at" class="text-xs text-[#858B93]">
                            {{ [m.lot?.lot_number ? `Lote ${m.lot.lot_number}` : null, m.lot?.expires_at ? `Cad. ${m.lot.expires_at.substring(0, 10)}` : null].filter(Boolean).join(' · ') }}
                        </p>
                    </td>
                    <td :class="['whitespace-nowrap px-4 py-3 text-right font-semibold', m.quantity >= 0 ? 'text-[#187744]' : 'text-[#941B2B]']">
                        {{ m.quantity > 0 ? '+' : '' }}{{ formatQty(m.quantity) }} {{ unit ?? m.item?.unit }}
                    </td>
                    <td class="whitespace-nowrap px-4 py-3 text-right text-[#292D32]">{{ formatQty(m.balance_after) }}</td>
                    <td class="px-4 py-3 text-xs text-[#525960]">
                        {{ [m.student ? `Alumno: ${m.student.registration_number} ${fullName(m.student.people)}` : null, m.reason, m.reference, m.supplier?.name, m.unit_cost !== null ? formatMoney(m.unit_cost) : null, m.user?.name].filter(Boolean).join(' · ') }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
