<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClassSchedule;
use App\Models\Space;
use App\Models\SpaceAvailability;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Horario de atención clínica de un consultorio (se administra desde la vista del espacio).
 */
class SpaceAvailabilityController extends Controller
{
    public function store(Request $request, Space $space): RedirectResponse
    {
        $data = $this->validated($request, $space);

        $space->availabilities()->create($data);

        return back()->with('success', 'Horario de disponibilidad agregado.');
    }

    public function update(Request $request, Space $space, SpaceAvailability $availability): RedirectResponse
    {
        abort_unless($availability->space_id === $space->id, 404);

        $availability->update($this->validated($request, $space, $availability));

        return back()->with('success', 'Horario de disponibilidad actualizado.');
    }

    public function destroy(Space $space, SpaceAvailability $availability): RedirectResponse
    {
        abort_unless($availability->space_id === $space->id, 404);

        $availability->delete();

        return back()->with('success', 'Horario de disponibilidad eliminado.');
    }

    private function validated(Request $request, Space $space, ?SpaceAvailability $current = null): array
    {
        $data = $request->validate([
            'academic_period_id' => ['nullable', 'exists:academic_periods,id'],
            'day_of_week' => ['required', 'integer', Rule::in(array_keys(ClassSchedule::DAYS))],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $data['start_time'] = SpaceAvailability::withSeconds($data['start_time']);
        $data['end_time'] = SpaceAvailability::withSeconds($data['end_time']);

        // Dos bloques que se enciman en el mismo día y periodo serían ambiguos.
        $overlaps = $space->availabilities()
            ->where('day_of_week', $data['day_of_week'])
            ->where('start_time', '<', $data['end_time'])
            ->where('end_time', '>', $data['start_time'])
            ->when(
                $data['academic_period_id'] ?? null,
                fn ($q, $periodId) => $q->forPeriod($periodId),
                fn ($q) => $q
            )
            ->when($current, fn ($q) => $q->whereKeyNot($current->id))
            ->exists();

        if ($overlaps) {
            throw ValidationException::withMessages([
                'start_time' => 'Ya existe un horario de disponibilidad que se encima con este.',
            ]);
        }

        return $data;
    }
}
