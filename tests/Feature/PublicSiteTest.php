<?php

namespace Tests\Feature;

use App\Models\AcademicActivity;
use App\Models\AcademicProgram;
use App\Models\Clinic;
use App\Models\People;
use App\Models\Procedure;
use App\Models\Specialty;
use App\Models\StudyPlan;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Sitio público: oferta académica, cursos y actividades, y atención clínica.
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private AcademicProgram $ortho;

    protected function setUp(): void
    {
        parent::setUp();

        $specialty = Specialty::create(['code' => 'ORTO', 'name' => 'Ortodoncia']);

        $this->ortho = AcademicProgram::create([
            'type' => 'especialidad', 'code' => 'EORT', 'name' => 'Especialidad en Ortodoncia', 'specialty_id' => $specialty->id,
            'description' => 'Formación clínica en ortodoncia.', 'duration_value' => 3, 'duration_unit' => 'anios', 'total_periods' => 6, 'is_active' => true,
        ]);
        $this->ortho->objectives()->create(['type' => 'perfil_egreso', 'description' => 'Diagnostica maloclusiones.', 'sort' => 1]);

        AcademicProgram::create(['type' => 'licenciatura', 'code' => 'LCD', 'name' => 'Licenciatura de Cirujano Dentista', 'is_active' => true]);
        AcademicProgram::create(['type' => 'maestria', 'code' => 'OLD', 'name' => 'Programa cerrado', 'is_active' => false]);

        $plan = StudyPlan::create(['academic_program_id' => $this->ortho->id, 'name' => 'Plan 2024', 'version' => '1', 'is_active' => true]);
        $first = Subject::create(['code' => 'ORT-101', 'name' => 'Biomecánica', 'credits' => 8, 'is_active' => true]);
        $second = Subject::create(['code' => 'ORT-201', 'name' => 'Clínica de ortodoncia', 'credits' => 12, 'is_active' => true]);
        $plan->subjects()->attach($first->id, ['period_number' => 1, 'is_required' => true, 'sort' => 1]);
        $plan->subjects()->attach($second->id, ['period_number' => 2, 'is_required' => false, 'sort' => 1]);

        $clinic = Clinic::create(['code' => 'CLI', 'name' => 'Clínica de Posgrado', 'location' => 'Edificio B', 'is_active' => true]);
        $clinic->areas()->create(['name' => 'Ortodoncia', 'specialty_id' => $specialty->id, 'is_active' => true]);
        Clinic::create(['code' => 'OFF', 'name' => 'Clínica cerrada', 'is_active' => false]);

        Procedure::create(['code' => 'PR-1', 'name' => 'Colocación de brackets', 'specialty_id' => $specialty->id, 'is_active' => true]);
        Procedure::create(['code' => 'PR-2', 'name' => 'Profilaxis', 'category' => 'Prevención', 'is_active' => true]);
    }

    private function activity(array $data): AcademicActivity
    {
        return AcademicActivity::create([
            'type' => 'curso', 'modality' => 'presencial', 'status' => 'abierta', 'min_attendance' => 80,
            'starts_at' => today()->addWeek(), 'title' => 'Curso',
            ...$data,
        ]);
    }

    public function test_home_shows_academic_offer_activities_and_clinics(): void
    {
        $upcoming = $this->activity(['title' => 'Congreso de ortodoncia', 'type' => 'congreso', 'capacity' => 2]);
        $upcoming->participants()->create(['people_id' => People::create(['first_name' => 'Ana', 'paternal_surname' => 'Privada'])->id]);
        $this->activity(['title' => 'Taller pasado', 'starts_at' => today()->subMonth(), 'status' => 'concluida']);
        $this->activity(['title' => 'Cancelado', 'status' => 'cancelada']);

        $this->get('/')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Home')
                ->where('stats.programs', 2)
                ->where('stats.clinics', 1)
                ->where('levels.0.level', 'licenciatura')
                ->where('levels.1.programs.0.code', 'EORT')
                ->has('upcoming', 1)
                ->where('upcoming.0.title', 'Congreso de ortodoncia')
                ->where('upcoming.0.available', 1)
                ->has('recent', 1)
                ->has('clinics', 1)
                ->where('specialties.0.program_code', 'EORT')
                ->where('servicesCount', Procedure::where('is_active', true)->count()));

        // Nada de datos personales de los participantes.
        $this->get('/')->assertDontSee('Privada');
    }

    public function test_program_detail_with_plan_and_profile(): void
    {
        $this->get('/oferta-academica/EORT')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Program')
                ->where('program.name', 'Especialidad en Ortodoncia')
                ->where('program.specialty', 'Ortodoncia')
                ->where('program.objectives.perfil_egreso.0', 'Diagnostica maloclusiones.')
                ->where('plan.subjects_count', 2)
                ->where('plan.credits', 20)
                ->where('plan.periods.0.subjects.0.name', 'Biomecánica')
                ->where('plan.periods.1.subjects.0.required', false));

        $this->get('/oferta-academica/OLD')->assertNotFound();
        $this->get('/oferta-academica')->assertOk()->assertInertia(fn (Assert $page) => $page->component('public/Programs')->has('levels', 2));
    }

    public function test_activities_list_detail_and_cancelled_hidden(): void
    {
        $course = $this->activity(['title' => 'Seminario de endodoncia', 'type' => 'seminario', 'description' => 'Actualización clínica.']);
        $cancelled = $this->activity(['title' => 'Cancelado', 'status' => 'cancelada']);

        $this->get('/cursos-y-actividades?tipo=seminario')
            ->assertInertia(fn (Assert $page) => $page->component('public/Activities')->has('upcoming', 1)->where('type', 'seminario'));

        $this->get("/cursos-y-actividades/{$course->id}")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('public/Activity')->where('activity.description', 'Actualización clínica.'));

        $this->get("/cursos-y-actividades/{$cancelled->id}")->assertNotFound();
    }

    public function test_clinical_page_groups_services(): void
    {
        $this->get('/atencion-clinica')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('public/Clinical')
                ->has('clinics', 1)
                ->where('clinics.0.areas.0.specialty', 'Ortodoncia')
                ->where('services', fn ($groups) => collect(collect($groups)->firstWhere('group', 'Ortodoncia')['items'] ?? [])->contains('Colocación de brackets')
                    && collect(collect($groups)->firstWhere('group', 'Prevención')['items'] ?? [])->contains('Profilaxis')));
    }

    public function test_public_pages_work_without_data(): void
    {
        AcademicProgram::query()->delete();
        Clinic::query()->delete();

        foreach (['/', '/oferta-academica', '/cursos-y-actividades', '/atencion-clinica'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
