<?php

namespace App\Reports\Definitions;

use App\Models\AcademicProgram;
use App\Models\Cohort;
use App\Models\StudentEnrollment;
use App\Reports\Column;
use App\Reports\Report;
use App\Support\Labels;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class StudentsReport extends Report
{
    public function key(): string
    {
        return 'alumnos';
    }

    public function title(): string
    {
        return 'Alumnos';
    }

    public function description(): string
    {
        return 'Padrón de alumnos por programa, generación y situación.';
    }

    public function section(): string
    {
        return 'Académico';
    }

    public function permission(): string
    {
        return 'students.view';
    }

    public function icon(): string
    {
        return 'GraduationCap';
    }

    public function filters(): array
    {
        return [
            self::select('academic_program_id', 'Programa', self::records(AcademicProgram::orderBy('name')->get(['id', 'code', 'name'])), 'Todos los programas'),
            self::select('cohort_id', 'Generación', self::records(Cohort::orderByDesc('id')->get(['id', 'name'])), 'Todas las generaciones'),
            self::select('status', 'Situación', Labels::options(StudentEnrollment::STATUSES), 'Todas'),
            self::search('Nombre, CURP o matrícula...'),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('registration_number', 'Matrícula'),
            Column::make('name', 'Nombre'),
            Column::make('program', 'Programa'),
            Column::make('cohort', 'Generación'),
            Column::make('started_at', 'Ingreso')->date(),
            Column::make('curp', 'CURP')->muted(),
            Column::make('contact', 'Contacto')->muted(),
            Column::make('status', 'Situación')->status(),
        ];
    }

    public function query(array $filters): Builder
    {
        return StudentEnrollment::query()
            ->with([
                'student:id,people_id,registration_number',
                'student.people:id,first_name,middle_name,paternal_surname,maternal_surname,curp,phone,personal_email',
                'academicProgram:id,name',
                'cohort:id,name',
            ])
            ->when($filters['academic_program_id'] ?? null, fn ($q, $id) => $q->where('academic_program_id', $id))
            ->when($filters['cohort_id'] ?? null, fn ($q, $id) => $q->where('cohort_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->whereHas('student', fn ($q) => $q
                ->where('registration_number', 'like', "%{$search}%")
                ->orWhereHas('people', fn ($q) => $q->search($search))))
            ->orderBy(
                \App\Models\StudentProfile::select('registration_number')->whereColumn('student_profiles.id', 'student_enrollments.student_profile_id')
            );
    }

    public function row(Model $record): array
    {
        $people = $record->student?->people;

        return [
            'registration_number' => $record->student?->registration_number,
            'name' => $people?->full_name,
            'program' => $record->academicProgram?->name,
            'cohort' => $record->cohort?->name,
            'started_at' => $record->started_at,
            'curp' => $people?->curp,
            'contact' => collect([$people?->phone, $people?->personal_email])->filter()->implode(' · '),
            'status' => $record->status,
        ];
    }

    public function summary(Builder $query, array $filters): array
    {
        $byStatus = self::countBy($query, 'status');

        return [
            ['label' => 'Alumnos', 'value' => Column::formatNumber(array_sum($byStatus))],
            ['label' => 'Activos', 'value' => Column::formatNumber($byStatus['activo'] ?? 0), 'tone' => 'positive'],
            ['label' => 'Bajas', 'value' => Column::formatNumber($byStatus['baja'] ?? 0), 'tone' => 'negative'],
            ['label' => 'Egresados', 'value' => Column::formatNumber($byStatus['egresado'] ?? 0)],
        ];
    }
}
