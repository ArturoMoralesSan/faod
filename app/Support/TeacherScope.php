<?php

namespace App\Support;

use App\Models\AcademicGroup;
use App\Models\Clinic;
use App\Models\Patient;
use App\Models\StudentEnrollment;
use App\Models\TreatmentPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Quien navega con el rol docente solo ve lo suyo: los grupos donde da clase,
 * los alumnos de esos grupos, los pacientes que supervisa y las clínicas
 * donde es supervisor. Con cualquier otro rol no se filtra nada.
 */
class TeacherScope
{
    /** Docente a filtrar; null si no aplica. 0 si es docente sin expediente (no ve nada). */
    public static function teacherId(?User $user): ?int
    {
        if (! $user || $user->currentRole()?->name !== 'docente') {
            return null;
        }

        return $user->people?->teacherProfile?->id ?? 0;
    }

    public static function apply(Builder $query, ?int $teacherId): Builder
    {
        if ($teacherId === null) {
            return $query;
        }

        $mine = fn ($q) => $q->where('teacher_profile_id', $teacherId);

        return match ($query->getModel()::class) {
            AcademicGroup::class => $query->whereHas('classSections', $mine),
            StudentEnrollment::class => $query->whereHas('student.groups.classSections', $mine),
            Clinic::class => $query->whereHas('supervisors', $mine),
            Patient::class => $query->where(fn ($q) => $q
                ->whereHas('appointments', $mine)
                ->orWhereHas('consultations', $mine)
                ->orWhereHas('diagnoses', $mine)
                ->orWhereHas('treatmentPlans', $mine)
                ->orWhereHas('treatmentProcedures', $mine)),
            TreatmentPlan::class => $query->where(fn ($q) => $q
                ->where('teacher_profile_id', $teacherId)
                ->orWhereHas('patient', fn ($q) => self::apply($q, $teacherId))),
            default => $query,
        };
    }

    /** 404 si el registro no es del docente que navega. */
    public static function ensureVisible(Model $model, ?User $user): void
    {
        $teacherId = self::teacherId($user);

        if ($teacherId === null) {
            return;
        }

        abort_unless(self::apply($model->newQuery(), $teacherId)->whereKey($model->getKey())->exists(), 404);
    }
}
