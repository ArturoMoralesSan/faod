<?php

namespace Database\Seeders\Demo;

use App\Models\AcademicPeriod;
use App\Models\AcademicProgram;
use App\Models\ClassSchedule;
use App\Models\Clinic;
use App\Models\ClinicalSession;
use App\Models\ClinicEquipment;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Supervisores de cada clínica, sesiones clínicas (prácticas de grupo de las
 * últimas tres semanas y las próximas dos) con asistencia, e incidencias.
 */
class FaodClinicOperationDemoSeeder extends Seeder
{
    use DemoHelpers;

    /** Clínica => programa cuyo coordinador es el responsable. */
    private const RESPONSIBLE = [
        'CLIN-LIC' => 'LCD',
        'CLIN-PERIO' => 'EPI',
        'CLIN-ORTO' => 'EORT',
        'CLIN-ODP' => 'EODP',
    ];

    private const TOPICS = [
        'Historia clínica y diagnóstico integral', 'Control de placa y profilaxis', 'Restauraciones con resina compuesta',
        'Raspado y alisado radicular', 'Tratamiento de conductos', 'Toma de impresiones y modelos de estudio',
        'Exodoncias simples', 'Revisión de casos clínicos', 'Ajuste de aparatología', 'Selladores y aplicación de flúor',
    ];

    public function run(): void
    {
        $this->supervisors();
        $this->sessions();
        $this->incidents();
    }

    private function supervisors(): void
    {
        $since = AcademicPeriod::current()?->starts_at?->toDateString();

        foreach (Clinic::with('areas')->get() as $clinic) {
            $responsible = isset(self::RESPONSIBLE[$clinic->code])
                ? AcademicProgram::firstWhere('code', self::RESPONSIBLE[$clinic->code])?->coordinators()->first()
                : TeacherProfile::query()
                    ->whereHas('degrees', fn ($q) => $q->where('title', 'like', '%Radiología%'))
                    ->first();

            if ($responsible) {
                $clinic->supervisors()->create([
                    'teacher_profile_id' => $responsible->id,
                    'role' => 'responsable',
                    'starts_at' => $since,
                ]);
            }

            foreach ($clinic->areas as $area) {
                $teachers = TeacherProfile::whereHas('specialties', fn ($q) => $q->whereKey($area->specialty_id))
                    ->when($responsible, fn ($q) => $q->whereKeyNot($responsible->id))
                    ->get();

                foreach ($teachers as $teacher) {
                    $clinic->supervisors()->create([
                        'clinic_area_id' => $area->id,
                        'teacher_profile_id' => $teacher->id,
                        'role' => 'supervisor',
                        'starts_at' => $since,
                    ]);
                }
            }
        }
    }

    private function sessions(): void
    {
        $period = AcademicPeriod::current();
        $from = today()->subWeeks(3)->max($period->starts_at);
        $to = today()->addWeeks(2)->min($period->ends_at);

        $schedules = ClassSchedule::query()
            ->with(['space', 'classSection.group.students', 'classSection.teacher.people'])
            ->whereHas('space', fn ($q) => $q->where('type', 'otro')->whereNotNull('clinic_id'))
            ->get();

        foreach ($schedules as $schedule) {
            $section = $schedule->classSection;
            $creator = $section->teacher?->people?->user_id;

            for ($date = $from->copy(); $date->lte($to); $date = $date->addDay()) {
                if ($date->dayOfWeekIso !== $schedule->day_of_week) {
                    continue;
                }

                $past = $date->lt(today());
                $cancelled = $past && $this->chance(4);

                $session = ClinicalSession::create([
                    'clinic_id' => $schedule->space->clinic_id,
                    'space_id' => $schedule->space_id,
                    'academic_period_id' => $period->id,
                    'academic_group_id' => $section->academic_group_id,
                    'subject_id' => $section->subject_id,
                    'supervisor_id' => $section->teacher_profile_id,
                    'date' => $date->toDateString(),
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'status' => $cancelled ? 'cancelada' : ($past ? 'concluida' : ($date->isToday() ? 'en_curso' : 'programada')),
                    'topic' => $this->pick(self::TOPICS),
                    'notes' => $cancelled ? 'Suspendida por mantenimiento del compresor.' : null,
                    'created_by' => $creator,
                ]);

                foreach ($section->group->students as $student) {
                    $attendance = 'pendiente';

                    if ($past && ! $cancelled) {
                        $roll = mt_rand(1, 100);
                        $attendance = $roll <= 88 ? 'asistio' : ($roll <= 95 ? 'falta' : 'justificada');
                    }

                    $session->students()->attach($student->id, [
                        'attendance' => $attendance,
                        'notes' => $attendance === 'justificada' ? 'Presentó justificante médico.' : null,
                    ]);
                }
            }
        }
    }

    private function incidents(): void
    {
        $reporter = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'Asistente dental'))->first();
        $resolver = User::whereHas('people.staffProfile', fn ($q) => $q->where('position', 'like', 'Auxiliar de CEyE%'))->first();
        $lic = Clinic::firstWhere('code', 'CLIN-LIC');

        $broken = ClinicEquipment::where('status', 'fuera_servicio')->first();
        $maintenance = ClinicEquipment::where('status', 'mantenimiento')->first();

        $incidents = [
            [$lic, $broken, 'equipo', 'alta', 'en_proceso', 'Lámpara de fotocurado sin encender', 'La lámpara de fotocurado de la clínica dejó de encender durante la práctica; se usó la de otra unidad.', 6, null],
            [$maintenance?->clinic ?? $lic, $maintenance, 'equipo', 'media', 'abierta', 'Fuga en la jeringa triple', 'La jeringa triple de la unidad gotea agua; la unidad se dejó fuera de uso.', 3, null],
            [$lic, null, 'bioseguridad', 'alta', 'resuelta', 'Indicador biológico positivo en autoclave', 'El indicador biológico semanal salió positivo. Se reprocesó la carga y se revisó el equipo.', 15, 'Se cambió el empaque de la puerta y la prueba de repetición salió negativa.'],
            [Clinic::firstWhere('code', 'CLIN-ODP') ?? $lic, null, 'paciente', 'baja', 'cerrada', 'Paciente con lipotimia leve', 'Paciente pediátrico presentó mareo tras la anestesia local; se recuperó en posición de Trendelenburg.', 10, 'Se dio aviso al tutor y se reprogramó la cita.'],
            [$lic, null, 'instalaciones', 'media', 'abierta', 'Falla en el aire acondicionado', 'El aire acondicionado de la clínica no enfría; temperatura alta por la tarde.', 1, null],
        ];

        foreach ($incidents as [$clinic, $equipment, $type, $severity, $status, $title, $description, $daysAgo, $resolution]) {
            $occurred = now()->subDays($daysAgo)->setTime(mt_rand(8, 13), mt_rand(0, 59));
            $closed = in_array($status, ['resuelta', 'cerrada'], true);

            $clinic->incidents()->create([
                'space_id' => $equipment?->space_id,
                'clinic_equipment_id' => $equipment?->id,
                'type' => $type,
                'severity' => $severity,
                'status' => $status,
                'title' => $title,
                'description' => $description,
                'occurred_at' => $occurred,
                'reported_by' => $reporter?->id,
                'resolution' => $resolution,
                'resolved_at' => $closed ? $occurred->copy()->addDays(2) : null,
                'resolved_by' => $closed ? $resolver?->id : null,
            ]);
        }
    }
}
