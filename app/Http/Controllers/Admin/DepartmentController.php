<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DepartmentRequest;
use App\Models\Department;
use App\Models\People;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(Request $request): Response
    {
        $departments = Department::query()
            ->with(['parent:id,name', 'head:id,first_name,paternal_surname,maternal_surname'])
            ->withCount(['children', 'staff'])
            ->when($request->search, fn ($q, $search) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            }))
            ->when($request->type, fn ($q, $type) => $q->where('type', $type))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('admin/departments/Index', [
            'departments' => $departments,
            'filters' => $request->only('search', 'type'),
            'types' => Department::TYPES,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/departments/Create', $this->formData());
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Departamento registrado correctamente.');
    }

    public function edit(Department $department): Response
    {
        return Inertia::render('admin/departments/Edit', [
            'department' => $department,
            ...$this->formData($department),
        ]);
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Departamento actualizado correctamente.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Departamento eliminado correctamente.');
    }

    private function formData(?Department $department = null): array
    {
        return [
            'types' => Department::TYPES,
            'parents' => Department::query()
                ->when($department, fn ($q) => $q->whereKeyNot($department->id))
                ->orderBy('name')
                ->get(['id', 'name', 'type']),
            'people' => People::query()
                ->whereHas('teacherProfile')
                ->orWhereHas('staffProfile')
                ->orderBy('paternal_surname')
                ->get(['id', 'first_name', 'paternal_surname', 'maternal_surname']),
        ];
    }
}
