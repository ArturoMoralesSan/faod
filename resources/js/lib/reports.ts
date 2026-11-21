import {
    Boxes,
    CalendarCheck,
    ClipboardList,
    Contact,
    FileChartColumn,
    GraduationCap,
    HandCoins,
    Package,
    Presentation,
    Receipt,
    Stethoscope,
    TriangleAlert,
    Trophy,
    Wallet,
} from 'lucide-vue-next';
import type { Component } from 'vue';

/** Íconos que usan las definiciones de reportes (App\Reports\Report::icon). */
const ICONS: Record<string, Component> = {
    Boxes,
    CalendarCheck,
    ClipboardList,
    Contact,
    FileChartColumn,
    GraduationCap,
    HandCoins,
    Package,
    Presentation,
    Receipt,
    Stethoscope,
    TriangleAlert,
    Trophy,
    Wallet,
};

export function reportIcon(name: string): Component {
    return ICONS[name] ?? FileChartColumn;
}

const iso = (date: Date): string => {
    const local = new Date(date.getTime() - date.getTimezoneOffset() * 60000);

    return local.toISOString().slice(0, 10);
};

export interface PeriodPreset {
    key: string;
    label: string;
    range: () => [string, string] | null;
}

/** Atajos de periodo; null = todas las fechas. */
export const PERIOD_PRESETS: PeriodPreset[] = [
    { key: 'today', label: 'Hoy', range: () => [iso(new Date()), iso(new Date())] },
    {
        key: 'week',
        label: 'Esta semana',
        range: () => {
            const now = new Date();
            const monday = new Date(now);
            monday.setDate(now.getDate() - ((now.getDay() + 6) % 7));

            return [iso(monday), iso(now)];
        },
    },
    {
        key: 'month',
        label: 'Este mes',
        range: () => {
            const now = new Date();

            return [iso(new Date(now.getFullYear(), now.getMonth(), 1)), iso(now)];
        },
    },
    {
        key: 'last-month',
        label: 'Mes pasado',
        range: () => {
            const now = new Date();

            return [iso(new Date(now.getFullYear(), now.getMonth() - 1, 1)), iso(new Date(now.getFullYear(), now.getMonth(), 0))];
        },
    },
    {
        key: 'year',
        label: 'Este año',
        range: () => {
            const now = new Date();

            return [iso(new Date(now.getFullYear(), 0, 1)), iso(now)];
        },
    },
    { key: 'all', label: 'Todo', range: () => null },
];
