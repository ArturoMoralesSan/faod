<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OdontogramEntry;
use App\Models\Patient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Registro de estados del odontograma. No se edita: cada cambio es un registro
 * nuevo y así queda el historial de cada diente.
 */
class OdontogramController extends Controller
{
    /**
     * Guarda el estado de un diente: el diente completo y/o varias caras a la vez.
     */
    public function store(Request $request, Patient $patient): RedirectResponse
    {
        $data = $request->validate([
            'tooth' => ['required', Rule::in(OdontogramEntry::teeth())],
            'patient_diagnosis_id' => [
                'nullable',
                Rule::exists('patient_diagnoses', 'id')->where('patient_id', $patient->id),
            ],
            'notes' => ['nullable', 'string', 'max:255'],
            'entries' => ['required', 'array', 'min:1'],
            'entries.*.surface' => ['nullable', 'distinct', Rule::in(OdontogramEntry::SURFACES)],
            'entries.*.condition' => ['required', 'string'],
        ]);

        foreach ($data['entries'] as $index => $entry) {
            $allowed = empty($entry['surface']) ? OdontogramEntry::TOOTH_CONDITIONS : OdontogramEntry::SURFACE_CONDITIONS;

            if (! in_array($entry['condition'], $allowed, true)) {
                return back()->withErrors(["entries.{$index}.condition" => 'Condición no válida para esa cara.']);
            }
        }

        DB::transaction(function () use ($data, $patient, $request) {
            foreach ($data['entries'] as $entry) {
                $patient->odontogramEntries()->create([
                    'tooth' => $data['tooth'],
                    'surface' => $entry['surface'] ?? null,
                    'condition' => $entry['condition'],
                    'patient_diagnosis_id' => $data['patient_diagnosis_id'] ?? null,
                    'notes' => $data['notes'] ?? null,
                    'recorded_at' => now(),
                    'created_by' => $request->user()->id,
                ]);
            }
        });

        return back()->with('success', "Odontograma actualizado (diente {$data['tooth']}).");
    }

    /** Elimina un registro capturado por error. */
    public function destroy(Patient $patient, OdontogramEntry $entry): RedirectResponse
    {
        abort_unless($entry->patient_id === $patient->id, 404);

        $entry->delete();

        return back()->with('success', 'Registro del odontograma eliminado.');
    }
}
