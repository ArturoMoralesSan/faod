<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Revisiones docentes: lista de pendientes y registro de decisiones.
 */
class ReviewController extends Controller
{
    /**
     * Registros pendientes de revisión. Un docente ve los que tiene asignados
     * (o sin asignar); con "todos" se ven los de cualquier docente.
     */
    public function index(Request $request): Response
    {
        $teacherId = $request->user()->people?->teacherProfile?->id;
        $scope = $request->input('scope', $teacherId ? 'mine' : 'all');

        $items = collect(Review::REVIEWABLE_TYPES)
            ->flatMap(function (string $class, string $alias) use ($request, $teacherId, $scope) {
                return $class::query()
                    ->whereIn('review_status', $request->input('status') === 'correcciones' ? ['correcciones'] : ['pendiente'])
                    ->when($scope === 'mine' && $teacherId, fn (Builder $q) => $q->where(fn ($q) => $q
                        ->where('teacher_profile_id', $teacherId)
                        ->orWhereNull('teacher_profile_id')))
                    ->with($this->relationsFor($alias))
                    ->oldest('updated_at')
                    ->limit(200)
                    ->get()
                    ->map(fn ($record) => [
                        'type' => $alias,
                        'id' => $record->id,
                        'title' => $record->reviewTitle(),
                        'url' => $record->reviewUrl(),
                        'patient' => $record->patient?->people?->full_name,
                        'record_number' => $record->patient?->record_number,
                        'student' => $record->student?->people?->full_name,
                        'teacher' => $record->teacher?->people?->full_name,
                        'status' => $record->review_status,
                        'updated_at' => $record->updated_at,
                    ]);
            })
            ->sortBy('updated_at')
            ->values();

        return Inertia::render('admin/reviews/Index', [
            'items' => $items,
            'filters' => ['scope' => $scope, 'status' => $request->input('status', 'pendiente')],
            'isTeacher' => (bool) $teacherId,
            'decisions' => Review::DECISIONS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reviewable_type' => ['required', Rule::in(array_keys(Review::REVIEWABLE_TYPES))],
            'reviewable_id' => ['required', 'integer'],
            'decision' => ['required', Rule::in(Review::DECISIONS)],
            'comments' => [
                Rule::requiredIf(in_array($request->input('decision'), ['correcciones', 'rechazado'], true)),
                'nullable',
                'string',
            ],
        ], [
            'comments.required' => 'Explica qué debe corregirse o por qué se rechaza.',
        ]);

        $record = Review::REVIEWABLE_TYPES[$data['reviewable_type']]::findOrFail($data['reviewable_id']);

        $record->reviews()->create([
            'reviewer_id' => $request->user()->id,
            'teacher_profile_id' => $request->user()->people?->teacherProfile?->id,
            'decision' => $data['decision'],
            'comments' => $data['comments'] ?? null,
        ]);

        return back()->with('success', 'Revisión registrada.');
    }

    private function relationsFor(string $alias): array
    {
        $people = ':id,first_name,middle_name,paternal_surname,maternal_surname';

        return match ($alias) {
            'treatment_plan' => [
                "patient.people{$people}",
                "student.people{$people}",
                "teacher.people{$people}",
            ],
            'treatment_procedure' => [
                "patient.people{$people}",
                "student.people{$people}",
                "teacher.people{$people}",
                'procedure:id,code,name',
            ],
            default => [
                "patient.people{$people}",
                "student.people{$people}",
                "teacher.people{$people}",
                'diagnosis:id,code,name',
            ],
        };
    }
}
