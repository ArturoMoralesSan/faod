<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePeopleRequest;
use App\Http\Requests\Admin\UpdatePeopleRequest;
use App\Models\People;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class PeopleController extends Controller
{
    public function index(Request $request): Response
    {
        $people = People::query()
            ->with([
                'user:id,name,email',
                'studentProfile:id,people_id,registration_number,status',
                'teacherProfile:id,people_id,employee_number,status',
                'staffProfile:id,people_id,employee_number,position,status',
            ])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', "%{$search}%")
                        ->orWhere('paternal_surname', 'like', "%{$search}%")
                        ->orWhere('maternal_surname', 'like', "%{$search}%")
                        ->orWhere('curp', 'like', "%{$search}%")
                        ->orWhere('rfc', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->profile, function ($query, $profile) {
                match ($profile) {
                    'student' => $query->whereHas('studentProfile'),
                    'teacher' => $query->whereHas('teacherProfile'),
                    'staff' => $query->whereHas('staffProfile'),
                    default => null,
                };
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/People/Index', [
            'people' => $people,
            'filters' => $request->only('search', 'profile'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/People/Create');
    }

    public function store(StorePeopleRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();

            $account = null;

            if (! empty($data['account'])) {
                $account = User::create([
                    'name' => trim(
                        $data['first_name'].' '.
                        ($data['middle_name'] ?? '').' '.
                        $data['paternal_surname'].' '.
                        ($data['maternal_surname'] ?? '')
                    ),
                    'email' => $data['account']['email'],
                    'password' => $data['account']['password'],
                ]);
            }

            $personData = Arr::only($data, [
                'curp',
                'rfc',
                'first_name',
                'middle_name',
                'paternal_surname',
                'maternal_surname',
                'birth_date',
                'gender',
                'phone',
                'personal_email',
                'street',
                'exterior_number',
                'interior_number',
                'neighborhood',
                'postal_code',
                'city',
                'state',
                'country',
            ]);

            $personData['user_id'] = $account?->id;

            $person = People::create($personData);

            if (! empty($data['student_profile'])) {
                $person->studentProfile()->create($data['student_profile']);
                $this->assignProfileRole($account, 'alumno');
            }

            if (! empty($data['teacher_profile'])) {
                $person->teacherProfile()->create($data['teacher_profile']);
                $this->assignProfileRole($account, 'docente');
            }

            if (! empty($data['staff_profile'])) {
                $person->staffProfile()->create($data['staff_profile']);
                $this->assignProfileRole($account, 'personal_administrativo');
            }
        });

        return redirect()
            ->route('admin.people.index')
            ->with('success', 'Persona registrada correctamente.');
    }

    public function show(People $people): Response
    {
        $people->load([
            'user:id,name,email',
            'studentProfile',
            'teacherProfile',
            'staffProfile',
        ]);

        return Inertia::render('Admin/People/Show', [
            'person' => $people,
        ]);
    }

    public function edit(People $people): Response
    {
        $people->load([
            'user:id,name,email',
            'studentProfile',
            'teacherProfile',
            'staffProfile',
        ]);

        return Inertia::render('Admin/People/Edit', [
            'person' => $people,
        ]);
    }

    public function update(
        UpdatePeopleRequest $request,
        People $people
    ): RedirectResponse {
        DB::transaction(function () use ($request, $people) {
            $data = $request->validated();

            $personData = Arr::only($data, [
                'curp',
                'rfc',
                'first_name',
                'middle_name',
                'paternal_surname',
                'maternal_surname',
                'birth_date',
                'gender',
                'phone',
                'personal_email',
                'street',
                'exterior_number',
                'interior_number',
                'neighborhood',
                'postal_code',
                'city',
                'state',
                'country',
            ]);

            $people->update($personData);

            // Actualizar o crear cuenta, si se enviaron sus datos.
            if (isset($data['account'])) {
                if ($people->user) {
                    $accountData = [
                        'email' => $data['account']['email'],
                    ];

                    if (! empty($data['account']['password'])) {
                        $accountData['password'] = $data['account']['password'];
                    }

                    $accountData['name'] = $people->fresh()->full_name;

                    $people->user->update($accountData);
                } elseif (! empty($data['account']['password'])) {
                    $account = User::create([
                        'name' => $people->fresh()->full_name,
                        'email' => $data['account']['email'],
                        'password' => $data['account']['password'],
                    ]);

                    $people->update(['user_id' => $account->id]);
                }
            }

            $account = $people->user;

            if (array_key_exists('student_profile', $data)) {
                if ($data['student_profile'] === null) {
                    $people->studentProfile()->delete();
                } else {
                    $people->studentProfile()->updateOrCreate(
                        ['people_id' => $people->id],
                        $data['student_profile']
                    );

                    $this->assignProfileRole($account, 'alumno');
                }
            }

            if (array_key_exists('teacher_profile', $data)) {
                if ($data['teacher_profile'] === null) {
                    $people->teacherProfile()->delete();
                } else {
                    $people->teacherProfile()->updateOrCreate(
                        ['people_id' => $people->id],
                        $data['teacher_profile']
                    );

                    $this->assignProfileRole($account, 'docente');
                }
            }

            if (array_key_exists('staff_profile', $data)) {
                if ($data['staff_profile'] === null) {
                    $people->staffProfile()->delete();
                } else {
                    $people->staffProfile()->updateOrCreate(
                        ['people_id' => $people->id],
                        $data['staff_profile']
                    );

                    $this->assignProfileRole($account, 'personal_administrativo');
                }
            }
        });

        return redirect()
            ->route('admin.people.index')
            ->with('success', 'Persona actualizada correctamente.');
    }

    public function destroy(People $people): RedirectResponse
    {
        $people->delete();

        return redirect()
            ->route('admin.people.index')
            ->with('success', 'Persona eliminada correctamente.');
    }

    private function assignProfileRole(?User $user, string $role): void
    {
        if (
            $user &&
            \Spatie\Permission\Models\Role::where('name', $role)
                ->where('guard_name', 'web')
                ->exists()
        ) {
            $user->assignRole($role);
        }
    }
}
