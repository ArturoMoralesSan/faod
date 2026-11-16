<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassScheduleRequest;
use App\Models\ClassSchedule;
use Illuminate\Http\RedirectResponse;

/**
 * Bloques de horario de las materias de un grupo.
 * Los choques de aula, docente y grupo se validan en ClassScheduleRequest.
 */
class ClassScheduleController extends Controller
{
    public function store(ClassScheduleRequest $request): RedirectResponse
    {
        ClassSchedule::create($request->validated());

        return back()->with('success', 'Horario agregado correctamente.');
    }

    public function update(ClassScheduleRequest $request, ClassSchedule $classSchedule): RedirectResponse
    {
        $classSchedule->update($request->validated());

        return back()->with('success', 'Horario actualizado correctamente.');
    }

    public function destroy(ClassSchedule $classSchedule): RedirectResponse
    {
        $classSchedule->delete();

        return back()->with('success', 'Horario eliminado correctamente.');
    }
}
