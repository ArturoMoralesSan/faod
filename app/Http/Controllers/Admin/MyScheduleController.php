<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcademicGroup;
use App\Models\AcademicPeriod;
use App\Models\ClassSchedule;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Horario del alumno que inició sesión: solo los grupos en los que está inscrito.
 */
class MyScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $period = $this->period($request);

        $groups = $this->groups($request, $period)
            ->load([
                'cohort.academicProgram:id,name,code',
                'classSections.subject:id,code,name',
                'classSections.teacher.people:id,first_name,paternal_surname,maternal_surname',
                'classSections.schedules.space:id,code,name',
            ]);

        return Inertia::render('admin/my-schedule/Index', [
            'groups' => $groups,
            'days' => ClassSchedule::DAYS,
            'academicPeriodId' => $period?->id,
            'periodName' => $period?->name,
            'periods' => AcademicPeriod::orderByDesc('starts_at')->get(['id', 'name']),
            'hasProfile' => (bool) $request->user()->people?->studentProfile,
        ]);
    }

    /** PDF del horario de uno de sus grupos. */
    public function pdf(Request $request, AcademicGroup $group): HttpResponse
    {
        abort_unless(
            $group->students()->whereKey($request->user()->people?->studentProfile?->id)->exists(),
            403,
        );

        return app(SchedulePrintController::class)->group($request, $group);
    }

    /** @return Collection<int, AcademicGroup> */
    private function groups(Request $request, ?AcademicPeriod $period)
    {
        $studentId = $request->user()->people?->studentProfile?->id;

        if (! $studentId || ! $period) {
            return AcademicGroup::query()->whereRaw('1 = 0')->get();
        }

        return AcademicGroup::query()
            ->where('academic_period_id', $period->id)
            ->whereHas('students', fn ($q) => $q->whereKey($studentId))
            ->orderBy('code')
            ->get(['id', 'code', 'cohort_id', 'academic_period_id', 'period_number']);
    }

    private function period(Request $request): ?AcademicPeriod
    {
        return $request->integer('academic_period_id')
            ? AcademicPeriod::find($request->integer('academic_period_id'))
            : AcademicPeriod::current();
    }
}
