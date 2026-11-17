<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicActivity;
use App\Models\ActivityInstructor;
use App\Models\ActivityParticipant;
use App\Models\People;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Inscritos, ponentes y constancias de una actividad académica.
 */
class ActivityParticipantController extends Controller
{
    /**
     * Inscribe a una persona registrada o da de alta a un participante externo.
     */
    public function store(Request $request, AcademicActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'people_id' => ['nullable', Rule::exists('people', 'id')->whereNull('deleted_at')],
            'first_name' => ['required_without:people_id', 'nullable', 'string', 'max:100'],
            'paternal_surname' => ['required_without:people_id', 'nullable', 'string', 'max:100'],
            'maternal_surname' => ['nullable', 'string', 'max:100'],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'payment_status' => ['nullable', Rule::in(ActivityParticipant::PAYMENT_STATUSES)],
        ]);

        if ($activity->isFull()) {
            throw ValidationException::withMessages(['people_id' => 'La actividad ya alcanzó su cupo.']);
        }

        DB::transaction(function () use ($data, $activity) {
            $peopleId = $data['people_id'] ?? People::create([
                'first_name' => $data['first_name'],
                'paternal_surname' => $data['paternal_surname'],
                'maternal_surname' => $data['maternal_surname'] ?? null,
                'personal_email' => $data['personal_email'] ?? null,
                'phone' => $data['phone'] ?? null,
            ])->id;

            if ($activity->participants()->where('people_id', $peopleId)->exists()) {
                throw ValidationException::withMessages(['people_id' => 'La persona ya está inscrita.']);
            }

            $activity->participants()->create([
                'people_id' => $peopleId,
                'payment_status' => $data['payment_status'] ?? ($activity->cost > 0 ? 'pendiente' : 'no_aplica'),
            ]);
        });

        return back()->with('success', 'Participante inscrito.');
    }

    /** Guarda asistencia, calificación y estado de todos los participantes. */
    public function update(Request $request, AcademicActivity $activity): RedirectResponse
    {
        $data = $request->validate([
            'participants' => ['required', 'array'],
            'participants.*.id' => ['required', 'integer'],
            'participants.*.status' => ['required', Rule::in(ActivityParticipant::STATUSES)],
            'participants.*.attendance' => ['nullable', 'integer', 'between:0,100'],
            'participants.*.grade' => ['nullable', 'numeric', 'between:0,100'],
            'participants.*.payment_status' => ['required', Rule::in(ActivityParticipant::PAYMENT_STATUSES)],
            'participants.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        foreach ($data['participants'] as $row) {
            $activity->participants()->whereKey($row['id'])->update(collect($row)->except('id')->all());
        }

        return back()->with('success', 'Participantes actualizados.');
    }

    public function destroy(AcademicActivity $activity, ActivityParticipant $participant): RedirectResponse
    {
        abort_unless($participant->academic_activity_id === $activity->id, 404);

        if ($participant->certificate_folio) {
            return back()->with('error', 'El participante ya tiene constancia; márcalo como baja en lugar de eliminarlo.');
        }

        $participant->delete();

        return back()->with('success', 'Participante eliminado.');
    }

    /**
     * Asigna folio de constancia a los acreditados que cumplen la asistencia mínima.
     */
    public function issueCertificates(AcademicActivity $activity): RedirectResponse
    {
        if (! $activity->issues_certificate) {
            return back()->with('error', 'Esta actividad no otorga constancia.');
        }

        $issued = DB::transaction(function () use ($activity) {
            $participants = $activity->participants()
                ->where('status', 'acreditado')
                ->whereNull('certificate_folio')
                ->where(fn ($q) => $q->whereNull('attendance')->orWhere('attendance', '>=', $activity->min_attendance))
                ->lockForUpdate()
                ->get();

            $year = now()->year;
            $last = ActivityParticipant::where('certificate_folio', 'like', "C-{$year}-%")->count();

            foreach ($participants as $participant) {
                $participant->update([
                    'certificate_folio' => sprintf('C-%d-%05d', $year, ++$last),
                    'certificate_issued_at' => now(),
                ]);
            }

            return $participants->count();
        });

        return back()->with(
            $issued ? 'success' : 'error',
            $issued ? "Se emitieron {$issued} constancia(s)." : 'No hay participantes acreditados pendientes de constancia (revisa estado y asistencia).'
        );
    }

    /** Constancia imprimible. */
    public function certificate(AcademicActivity $activity, ActivityParticipant $participant): Response
    {
        abort_unless($participant->academic_activity_id === $activity->id && $participant->certificate_folio, 404);

        return Inertia::render('admin/activities/Certificate', [
            'activity' => $activity->load('coordinator.people:id,first_name,middle_name,paternal_surname,maternal_surname'),
            'participant' => $participant->load('people:id,first_name,middle_name,paternal_surname,maternal_surname'),
        ]);
    }

    /* ------------------------------- Ponentes ------------------------------- */

    public function storeInstructor(Request $request, AcademicActivity $activity): RedirectResponse
    {
        $activity->instructors()->create($request->validate([
            'teacher_profile_id' => ['nullable', 'required_without:name', Rule::exists('teacher_profiles', 'id')->whereNull('deleted_at')],
            'name' => ['nullable', 'required_without:teacher_profile_id', 'string', 'max:255'],
            'role' => ['required', 'string', 'max:50'],
        ], [
            'teacher_profile_id.required_without' => 'Elige un docente o escribe el nombre del ponente externo.',
            'name.required_without' => 'Elige un docente o escribe el nombre del ponente externo.',
        ]));

        return back()->with('success', 'Ponente agregado.');
    }

    public function destroyInstructor(AcademicActivity $activity, ActivityInstructor $instructor): RedirectResponse
    {
        abort_unless($instructor->academic_activity_id === $activity->id, 404);

        $instructor->delete();

        return back()->with('success', 'Ponente eliminado.');
    }
}
