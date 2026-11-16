<?php

use App\Http\Controllers\Admin\AcademicGroupController;
use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AcademicProgramController;
use App\Http\Controllers\Admin\ActiveRoleController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\CatalogItemController;
use App\Http\Controllers\Admin\CatalogTypeController;
use App\Http\Controllers\Admin\ClassScheduleController;
use App\Http\Controllers\Admin\ClassSectionController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\KnowledgeAreaController;
use App\Http\Controllers\Admin\PeopleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpaceController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\StudentEnrollmentController;
use App\Http\Controllers\Admin\StudyPlanController;
use App\Http\Controllers\Admin\StudyPlanItemController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherRecordController;
use App\Http\Controllers\Admin\UniversityRegistrationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

/**
 * Middleware de permisos para un Route::resource: modulo.view|create|edit|delete
 */
$crud = fn (string $module) => [
    'index' => "permission:{$module}.view",
    'show' => "permission:{$module}.view",
    'create' => "permission:{$module}.create",
    'store' => "permission:{$module}.create",
    'edit' => "permission:{$module}.edit",
    'update' => "permission:{$module}.edit",
    'destroy' => "permission:{$module}.delete",
];

/*
|--------------------------------------------------------------------------
| Página pública
|--------------------------------------------------------------------------
*/

Route::get('/', [HomeController::class, 'index'])->name('home');
/*
|--------------------------------------------------------------------------
| Administración
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () use ($crud) {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        |
        | Página de bienvenida de Industrias Husky.
        |
        */

        Route::inertia('dashboard', 'Dashboard')
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Usuarios
        |--------------------------------------------------------------------------
        */

        Route::resource('users', UserController::class)
            ->middleware([
                'index' => 'permission:users.view',
                'create' => 'permission:users.create',
                'store' => 'permission:users.create',
                'edit' => 'permission:users.edit',
                'update' => 'permission:users.edit',
                'destroy' => 'permission:users.delete',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        Route::resource('roles', RoleController::class)
            ->middleware([
                'index' => 'permission:roles.view',
                'create' => 'permission:roles.create',
                'store' => 'permission:roles.create',
                'edit' => 'permission:roles.edit',
                'update' => 'permission:roles.edit',
                'destroy' => 'permission:roles.delete',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Permisos
        |--------------------------------------------------------------------------
        */

        Route::resource('permissions', PermissionController::class)
            ->middleware([
                'index' => 'permission:permissions.view',
                'create' => 'permission:permissions.create',
                'store' => 'permission:permissions.create',
                'edit' => 'permission:permissions.edit',
                'update' => 'permission:permissions.edit',
                'destroy' => 'permission:permissions.delete',
            ]);
        
        Route::resource('people', PeopleController::class)
            ->parameters(['people' => 'people'])
            ->middleware([
                'index' => 'permission:people.view',
                'create' => 'permission:people.create',
                'store' => 'permission:people.create',
                'show' => 'permission:people.view',
                'edit' => 'permission:people.edit',
                'update' => 'permission:people.edit',
                'destroy' => 'permission:people.delete',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Selector de rol (cambia el menú)
        |--------------------------------------------------------------------------
        */

        Route::put('active-role', [ActiveRoleController::class, 'update'])
            ->name('active-role.update');

        /*
        |--------------------------------------------------------------------------
        | Administración general
        |--------------------------------------------------------------------------
        */

        Route::resource('departments', DepartmentController::class)
            ->except('show')
            ->middleware($crud('departments'));

        Route::get('settings', [SettingController::class, 'index'])
            ->name('settings.index')
            ->middleware('permission:settings.view');
        Route::put('settings', [SettingController::class, 'update'])
            ->name('settings.update')
            ->middleware('permission:settings.edit');

        Route::resource('catalogs', CatalogTypeController::class)
            ->middleware($crud('catalogs'));

        Route::middleware('permission:catalogs.edit')->group(function () {
            Route::post('catalogs/{catalog}/items', [CatalogItemController::class, 'store'])
                ->name('catalogs.items.store');
            Route::put('catalog-items/{item}', [CatalogItemController::class, 'update'])
                ->name('catalog-items.update');
            Route::delete('catalog-items/{item}', [CatalogItemController::class, 'destroy'])
                ->name('catalog-items.destroy');
        });

        Route::post('attachments', [AttachmentController::class, 'store'])
            ->name('attachments.store');
        Route::get('attachments/{attachment}', [AttachmentController::class, 'download'])
            ->name('attachments.download');
        Route::delete('attachments/{attachment}', [AttachmentController::class, 'destroy'])
            ->name('attachments.destroy');

        /*
        |--------------------------------------------------------------------------
        | Programas académicos y especialidades
        |--------------------------------------------------------------------------
        */

        Route::resource('specialties', SpecialtyController::class)
            ->except('show')
            ->middleware($crud('specialties'));

        Route::resource('academic-programs', AcademicProgramController::class)
            ->middleware($crud('academic-programs'));

        Route::resource('academic-periods', AcademicPeriodController::class)
            ->except('show')
            ->middleware($crud('academic-periods'));

        Route::resource('cohorts', CohortController::class)
            ->middleware($crud('cohorts'));

        Route::resource('subjects', SubjectController::class)
            ->except('show')
            ->middleware($crud('subjects'));

        Route::resource('study-plans', StudyPlanController::class)
            ->middleware($crud('study-plans'));

        Route::middleware('permission:study-plans.edit')
            ->controller(StudyPlanItemController::class)
            ->group(function () {
                Route::post('study-plans/{study_plan}/modules', 'storeModule')->name('study-plans.modules.store');
                Route::put('academic-modules/{module}', 'updateModule')->name('academic-modules.update');
                Route::delete('academic-modules/{module}', 'destroyModule')->name('academic-modules.destroy');

                Route::post('study-plans/{study_plan}/subjects', 'attachSubject')->name('study-plans.subjects.store');
                Route::put('study-plans/{study_plan}/subjects/{subject}', 'updateSubject')->name('study-plans.subjects.update');
                Route::delete('study-plans/{study_plan}/subjects/{subject}', 'detachSubject')->name('study-plans.subjects.destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Clínicas y espacios
        |--------------------------------------------------------------------------
        */

        Route::resource('clinics', ClinicController::class)
            ->middleware($crud('clinics'));

        Route::resource('spaces', SpaceController::class)
            ->middleware($crud('spaces'));

        /*
        |--------------------------------------------------------------------------
        | Grupos y horarios
        |--------------------------------------------------------------------------
        */

        Route::resource('groups', AcademicGroupController::class)
            ->middleware($crud('groups'));

        Route::middleware('permission:schedules.edit')->group(function () {
            Route::post('groups/{group}/sections', [ClassSectionController::class, 'store'])
                ->name('groups.sections.store');
            Route::put('class-sections/{class_section}', [ClassSectionController::class, 'update'])
                ->name('class-sections.update');
            Route::delete('class-sections/{class_section}', [ClassSectionController::class, 'destroy'])
                ->name('class-sections.destroy');

            Route::resource('class-schedules', ClassScheduleController::class)
                ->only(['store', 'update', 'destroy']);
        });

        /*
        |--------------------------------------------------------------------------
        | Alumnos (control interno)
        |--------------------------------------------------------------------------
        */

        Route::resource('students', StudentEnrollmentController::class)
            ->parameters(['students' => 'student'])
            ->middleware($crud('students'));

        Route::middleware('permission:students.edit')->group(function () {
            Route::post('students/{student}/university-registrations', [UniversityRegistrationController::class, 'store'])
                ->name('students.university-registrations.store');
            Route::put('university-registrations/{registration}', [UniversityRegistrationController::class, 'update'])
                ->name('university-registrations.update');
            Route::delete('university-registrations/{registration}', [UniversityRegistrationController::class, 'destroy'])
                ->name('university-registrations.destroy');
        });

        /*
        |--------------------------------------------------------------------------
        | Docentes y especialistas
        |--------------------------------------------------------------------------
        */

        Route::resource('knowledge-areas', KnowledgeAreaController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->middleware($crud('knowledge-areas'));

        Route::get('teachers', [TeacherController::class, 'index'])
            ->name('teachers.index')
            ->middleware('permission:teachers.view');
        Route::get('teachers/{teacher}', [TeacherController::class, 'show'])
            ->name('teachers.show')
            ->middleware('permission:teachers.view');

        Route::middleware('permission:teachers.edit')->group(function () {
            Route::put('teachers/{teacher}/areas', [TeacherController::class, 'syncAreas'])
                ->name('teachers.areas.update');

            $recordTypes = 'degrees|certifications|experiences|availabilities';

            Route::post('teachers/{teacher}/{type}', [TeacherRecordController::class, 'store'])
                ->whereIn('type', explode('|', $recordTypes))
                ->name('teachers.records.store');
            Route::put('teachers/{teacher}/{type}/{record}', [TeacherRecordController::class, 'update'])
                ->whereIn('type', explode('|', $recordTypes))
                ->whereNumber('record')
                ->name('teachers.records.update');
            Route::delete('teachers/{teacher}/{type}/{record}', [TeacherRecordController::class, 'destroy'])
                ->whereIn('type', explode('|', $recordTypes))
                ->whereNumber('record')
                ->name('teachers.records.destroy');
        });
    });



require __DIR__.'/settings.php';
