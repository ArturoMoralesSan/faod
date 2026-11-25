<?php

use App\Http\Controllers\Admin\AcademicActivityController;
use App\Http\Controllers\Admin\AcademicGroupController;
use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AcademicProgramController;
use App\Http\Controllers\Admin\ActiveRoleController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\ActivityParticipantController;
use App\Http\Controllers\Admin\AppointmentController;
use App\Http\Controllers\Admin\AttachmentController;
use App\Http\Controllers\Admin\CatalogItemController;
use App\Http\Controllers\Admin\CatalogTypeController;
use App\Http\Controllers\Admin\ClassScheduleController;
use App\Http\Controllers\Admin\ClassSectionController;
use App\Http\Controllers\Admin\ClinicalSessionController;
use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\ClinicIncidentController;
use App\Http\Controllers\Admin\ClinicRecordController;
use App\Http\Controllers\Admin\CohortController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\DiagnosisController;
use App\Http\Controllers\Admin\Finance;
use App\Http\Controllers\Admin\InventoryCountController;
use App\Http\Controllers\Admin\InventoryItemController;
use App\Http\Controllers\Admin\InventoryMovementController;
use App\Http\Controllers\Admin\KnowledgeAreaController;
use App\Http\Controllers\Admin\MyScheduleController;
use App\Http\Controllers\Admin\OdontogramController;
use App\Http\Controllers\Admin\PatientController;
use App\Http\Controllers\Admin\PatientDiagnosisController;
use App\Http\Controllers\Admin\PatientRecordController;
use App\Http\Controllers\Admin\PeopleController;
use App\Http\Controllers\Admin\PeoplePhotoController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\ProcedureController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SchedulePrintController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SpaceAvailabilityController;
use App\Http\Controllers\Admin\SpaceController;
use App\Http\Controllers\Admin\SpecialtyController;
use App\Http\Controllers\Admin\StudentEnrollmentController;
use App\Http\Controllers\Admin\StudyPlanController;
use App\Http\Controllers\Admin\StudyPlanItemController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\TeacherRecordController;
use App\Http\Controllers\Admin\TreatmentItemController;
use App\Http\Controllers\Admin\TreatmentPlanController;
use App\Http\Controllers\Admin\UniversityRegistrationController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Http\Controllers\HomeController;
use App\Models\Charge;
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

// Sitio público: oferta académica, cursos y actividades, y atención clínica.
Route::name('public.')->group(function () {
    Route::get('oferta-academica', [HomeController::class, 'programs'])->name('programs');
    Route::get('oferta-academica/{code}', [HomeController::class, 'program'])->name('programs.show');
    Route::get('cursos-y-actividades', [HomeController::class, 'activities'])->name('activities');
    Route::get('cursos-y-actividades/{activity}', [HomeController::class, 'activity'])->whereNumber('activity')->name('activities.show');
    Route::get('atencion-clinica', [HomeController::class, 'clinical'])->name('clinical');
});
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
        | Panel principal del sistema.
        |
        */

        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Usuarios
        |--------------------------------------------------------------------------
        */

        Route::resource('users', UserController::class)
            ->permissions([
                'index' => 'permission:users.view',
                'show' => 'permission:users.view',
                'create' => 'permission:users.create',
                'store' => 'permission:users.create',
                'edit' => 'permission:users.edit',
                'update' => 'permission:users.edit',
                'destroy' => 'permission:users.delete',
            ]);

        Route::middleware('permission:users.edit')->group(function () {
            Route::patch('users/{user}/toggle-active', [UserController::class, 'toggleActive'])
                ->name('users.toggle-active');
            Route::post('users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])
                ->name('users.password-reset');
        });

        /*
        |--------------------------------------------------------------------------
        | Roles
        |--------------------------------------------------------------------------
        */

        Route::resource('roles', RoleController::class)
            ->permissions([
                'index' => 'permission:roles.view',
                'show' => 'permission:roles.view',
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
            ->permissions([
                'index' => 'permission:permissions.view',
                'show' => 'permission:permissions.view',
                'create' => 'permission:permissions.create',
                'store' => 'permission:permissions.create',
                'edit' => 'permission:permissions.edit',
                'update' => 'permission:permissions.edit',
                'destroy' => 'permission:permissions.delete',
            ]);

        // Fotografía (los permisos se validan en el controlador según el perfil).
        Route::get('people/{people}/photo', [PeoplePhotoController::class, 'show'])->name('people.photo.show');
        Route::post('people/{people}/photo', [PeoplePhotoController::class, 'update'])->name('people.photo.update');
        Route::delete('people/{people}/photo', [PeoplePhotoController::class, 'destroy'])->name('people.photo.destroy');

        Route::get('people/search', [PeopleController::class, 'search'])
            ->name('people.search')
            ->middleware('permission:people.view|activities.view');

        Route::resource('people', PeopleController::class)
            ->parameters(['people' => 'people'])
            ->permissions([
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
            ->permissions($crud('departments'));

        Route::get('settings', [SettingController::class, 'index'])
            ->name('settings.index')
            ->middleware('permission:settings.view');
        Route::put('settings', [SettingController::class, 'update'])
            ->name('settings.update')
            ->middleware('permission:settings.edit');

        Route::get('activity-log', [ActivityLogController::class, 'index'])
            ->name('activity-log.index')
            ->middleware('permission:activity-log.view');
        Route::get('activity-log/logins', [ActivityLogController::class, 'logins'])
            ->name('activity-log.logins')
            ->middleware('permission:activity-log.view');

        /*
        |--------------------------------------------------------------------------
        | Reportes (además, cada reporte exige el permiso de consulta de su módulo)
        |--------------------------------------------------------------------------
        */

        Route::middleware('permission:reports.view')->prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('{report}', [ReportController::class, 'show'])->name('show');
            Route::get('{report}/pdf', [ReportController::class, 'pdf'])->name('pdf');
            Route::get('{report}/csv', [ReportController::class, 'csv'])->name('csv');
        });

        Route::resource('catalogs', CatalogTypeController::class)
            ->permissions($crud('catalogs'));

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
            ->permissions($crud('specialties'));

        Route::resource('academic-programs', AcademicProgramController::class)
            ->permissions($crud('academic-programs'));

        Route::resource('academic-periods', AcademicPeriodController::class)
            ->except('show')
            ->permissions($crud('academic-periods'));

        Route::resource('cohorts', CohortController::class)
            ->permissions($crud('cohorts'));

        Route::resource('subjects', SubjectController::class)
            ->except('show')
            ->permissions($crud('subjects'));

        Route::resource('study-plans', StudyPlanController::class)
            ->permissions($crud('study-plans'));

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
            ->permissions($crud('clinics'));

        Route::resource('spaces', SpaceController::class)
            ->permissions($crud('spaces'));
        Route::get('spaces/{space}/schedule-pdf', [SchedulePrintController::class, 'space'])
            ->name('spaces.schedule-pdf')
            ->middleware('permission:spaces.view');

        // Áreas, supervisores y equipos de una clínica.
        Route::middleware('permission:clinics.edit')
            ->controller(ClinicRecordController::class)
            ->group(function () {
                $types = ClinicRecordController::TYPES;

                Route::post('clinics/{clinic}/{type}', 'store')
                    ->whereIn('type', $types)
                    ->name('clinics.records.store');
                Route::put('clinics/{clinic}/{type}/{record}', 'update')
                    ->whereIn('type', $types)
                    ->whereNumber('record')
                    ->name('clinics.records.update');
                Route::delete('clinics/{clinic}/{type}/{record}', 'destroy')
                    ->whereIn('type', $types)
                    ->whereNumber('record')
                    ->name('clinics.records.destroy');
            });

        // Horario de atención de consultorios.
        Route::resource('spaces.availabilities', SpaceAvailabilityController::class)
            ->only(['store', 'update', 'destroy'])
            ->middleware('permission:spaces.edit');

        Route::resource('clinical-sessions', ClinicalSessionController::class)
            ->permissions($crud('clinical-sessions'));
        Route::put('clinical-sessions/{clinical_session}/attendance', [ClinicalSessionController::class, 'attendance'])
            ->name('clinical-sessions.attendance')
            ->middleware('permission:clinical-sessions.edit');

        Route::resource('clinic-incidents', ClinicIncidentController::class)
            ->permissions($crud('clinic-incidents'));

        /*
        |--------------------------------------------------------------------------
        | Pacientes
        |--------------------------------------------------------------------------
        */

        // Antes del resource para que "search" no se tome como {patient}.
        Route::get('patients/search', [PatientController::class, 'search'])
            ->name('patients.search')
            ->middleware('permission:patients.view|appointments.view');

        Route::resource('patients', PatientController::class)
            ->permissions($crud('patients'));

        Route::middleware('permission:patients.edit')
            ->controller(PatientRecordController::class)
            ->group(function () {
                $types = PatientRecordController::TYPES;

                Route::post('patients/{patient}/{type}', 'store')
                    ->whereIn('type', $types)
                    ->name('patients.records.store');
                Route::put('patients/{patient}/{type}/{record}', 'update')
                    ->whereIn('type', $types)
                    ->whereNumber('record')
                    ->name('patients.records.update');
                Route::delete('patients/{patient}/{type}/{record}', 'destroy')
                    ->whereIn('type', $types)
                    ->whereNumber('record')
                    ->name('patients.records.destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Diagnósticos, odontograma y revisión docente
        |--------------------------------------------------------------------------
        */

        Route::resource('diagnoses', DiagnosisController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->permissions($crud('diagnosis-catalog'));

        Route::resource('patients.diagnoses', PatientDiagnosisController::class)
            ->only(['store', 'update', 'destroy'])
            ->permissions($crud('diagnoses'));

        Route::post('patients/{patient}/odontogram', [OdontogramController::class, 'store'])
            ->name('patients.odontogram.store')
            ->middleware('permission:diagnoses.create');
        Route::delete('patients/{patient}/odontogram/{entry}', [OdontogramController::class, 'destroy'])
            ->name('patients.odontogram.destroy')
            ->middleware('permission:diagnoses.delete');

        /*
        |--------------------------------------------------------------------------
        | Tratamientos
        |--------------------------------------------------------------------------
        */

        Route::resource('procedures', ProcedureController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->permissions($crud('procedure-catalog'));

        Route::post('patients/{patient}/treatment-plans', [TreatmentPlanController::class, 'store'])
            ->name('patients.treatment-plans.store')
            ->middleware('permission:treatments.create');
        Route::resource('treatment-plans', TreatmentPlanController::class)
            ->only(['show', 'update', 'destroy'])
            ->permissions($crud('treatments'));

        Route::middleware('permission:treatments.edit')
            ->controller(TreatmentItemController::class)
            ->group(function () {
                Route::post('treatment-plans/{treatment_plan}/stages', 'storeStage')->name('treatment-plans.stages.store');
                Route::put('treatment-stages/{stage}', 'updateStage')->name('treatment-stages.update');
                Route::delete('treatment-stages/{stage}', 'destroyStage')->name('treatment-stages.destroy');

                Route::post('treatment-plans/{treatment_plan}/procedures', 'storeProcedure')->name('treatment-plans.procedures.store');
                Route::put('treatment-procedures/{procedure}', 'updateProcedure')->name('treatment-procedures.update');
                Route::delete('treatment-procedures/{procedure}', 'destroyProcedure')->name('treatment-procedures.destroy');

                Route::post('treatment-procedures/{procedure}/materials', 'storeMaterial')->name('treatment-procedures.materials.store');
                Route::delete('procedure-materials/{material}', 'destroyMaterial')->name('procedure-materials.destroy');
            });

        /*
        |--------------------------------------------------------------------------
        | Agenda y citas
        |--------------------------------------------------------------------------
        */

        Route::resource('appointments', AppointmentController::class)
            ->except('show')
            ->permissions($crud('appointments'));

        Route::middleware('permission:appointments.edit')
            ->controller(AppointmentController::class)
            ->prefix('appointments/{appointment}')
            ->name('appointments.')
            ->group(function () {
                Route::patch('confirm', 'confirm')->name('confirm');
                Route::patch('attend', 'attend')->name('attend');
                Route::patch('no-show', 'noShow')->name('no-show');
                Route::patch('cancel', 'cancel')->name('cancel');
                Route::post('reschedule', 'reschedule')->name('reschedule');
                Route::post('remind', 'remind')->name('remind');
            });

        /*
        |--------------------------------------------------------------------------
        | Inventario y almacén
        |--------------------------------------------------------------------------
        */

        Route::get('inventory/movements', [InventoryMovementController::class, 'index'])
            ->name('inventory.movements.index')
            ->middleware('permission:inventory.view');
        Route::post('inventory/{inventory}/movements', [InventoryMovementController::class, 'store'])
            ->name('inventory.movements.store')
            ->middleware('permission:inventory.edit');

        Route::resource('inventory', InventoryItemController::class)
            ->only(['index', 'store', 'show', 'update', 'destroy'])
            ->permissions($crud('inventory'));

        Route::resource('inventory-counts', InventoryCountController::class)
            ->only(['index', 'store', 'show', 'update'])
            ->permissions([
                'index' => 'permission:inventory.view',
                'show' => 'permission:inventory.view',
                'store' => 'permission:inventory.edit',
                'update' => 'permission:inventory.edit',
            ]);
        Route::patch('inventory-counts/{inventory_count}/close', [InventoryCountController::class, 'close'])
            ->name('inventory-counts.close')
            ->middleware('permission:inventory.edit');

        Route::resource('suppliers', SupplierController::class)
            ->only(['index', 'store', 'update', 'destroy'])
            ->permissions($crud('suppliers'));

        Route::resource('warehouses', WarehouseController::class)
            ->only(['index', 'store', 'update'])
            ->permissions([
                'index' => 'permission:inventory.view',
                'store' => 'permission:inventory.edit',
                'update' => 'permission:inventory.edit',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Control administrativo y financiero
        |--------------------------------------------------------------------------
        */

        Route::prefix('finance')->name('finance.')->group(function () use ($crud) {
            Route::resource('concepts', Finance\FeeConceptController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->permissions($crud('fee-concepts'));

            Route::get('payers/search', [Finance\AccountController::class, 'search'])
                ->name('payers.search')
                ->middleware('permission:charges.view');
            Route::get('accounts/{type}/{id}', [Finance\AccountController::class, 'show'])
                ->whereIn('type', array_keys(Charge::PAYER_TYPES))
                ->whereNumber('id')
                ->name('accounts.show')
                ->middleware('permission:charges.view');

            Route::get('charges', [Finance\ChargeController::class, 'index'])->name('charges.index')->middleware('permission:charges.view');
            Route::post('charges', [Finance\ChargeController::class, 'store'])->name('charges.store')->middleware('permission:charges.create');
            Route::post('charges/bulk', [Finance\ChargeController::class, 'bulk'])->name('charges.bulk')->middleware('permission:charges.create');
            Route::patch('charges/{charge}/discount', [Finance\ChargeController::class, 'discount'])->name('charges.discount')->middleware('permission:charges.edit');
            Route::patch('charges/{charge}/cancel', [Finance\ChargeController::class, 'cancel'])->name('charges.cancel')->middleware('permission:charges.edit');
            Route::post('treatment-plans/{treatment_plan}/charges', [Finance\ChargeController::class, 'fromTreatmentPlan'])
                ->name('treatment-plans.charges')
                ->middleware('permission:charges.create');

            Route::get('payments', [Finance\PaymentController::class, 'index'])->name('payments.index')->middleware('permission:payments.view');
            Route::post('accounts/{type}/{id}/payments', [Finance\PaymentController::class, 'store'])
                ->whereIn('type', array_keys(Charge::PAYER_TYPES))
                ->whereNumber('id')
                ->name('payments.store')
                ->middleware('permission:payments.create');
            Route::post('incomes', [Finance\PaymentController::class, 'storeIncome'])->name('incomes.store')->middleware('permission:payments.create');
            Route::get('payments/{payment}', [Finance\PaymentController::class, 'show'])->name('payments.show')->middleware('permission:payments.view');
            Route::patch('payments/{payment}/cancel', [Finance\PaymentController::class, 'cancel'])->name('payments.cancel')->middleware('permission:payments.delete');

            Route::resource('expenses', Finance\ExpenseController::class)
                ->only(['index', 'store', 'update', 'destroy'])
                ->permissions($crud('expenses'));

            Route::get('cash-registers', [Finance\CashRegisterController::class, 'index'])->name('cash-registers.index')->middleware('permission:cash.view');
            Route::middleware('permission:cash.operate')->group(function () {
                Route::post('cash-registers', [Finance\CashRegisterController::class, 'store'])->name('cash-registers.store');
                Route::post('cash-registers/{cash_register}/open', [Finance\CashRegisterController::class, 'open'])->name('cash-registers.open');
                Route::patch('cash-sessions/{cash_session}/close', [Finance\CashRegisterController::class, 'close'])->name('cash-sessions.close');
                Route::post('cash-sessions/{cash_session}/movements', [Finance\CashRegisterController::class, 'movement'])->name('cash-sessions.movements.store');
            });
            Route::get('cash-sessions/{cash_session}', [Finance\CashRegisterController::class, 'session'])->name('cash-sessions.show')->middleware('permission:cash.view');
        });

        /*
        |--------------------------------------------------------------------------
        | Cursos y actividades académicas
        |--------------------------------------------------------------------------
        */

        Route::resource('activities', AcademicActivityController::class)
            ->permissions($crud('activities'));

        Route::middleware('permission:activities.edit')
            ->controller(ActivityParticipantController::class)
            ->prefix('activities/{activity}')
            ->name('activities.')
            ->group(function () {
                Route::post('participants', 'store')->name('participants.store');
                Route::put('participants', 'update')->name('participants.update');
                Route::delete('participants/{participant}', 'destroy')->name('participants.destroy');
                Route::post('certificates', 'issueCertificates')->name('certificates.issue');
                Route::post('instructors', 'storeInstructor')->name('instructors.store');
                Route::delete('instructors/{instructor}', 'destroyInstructor')->name('instructors.destroy');
            });
        Route::get('activities/{activity}/participants/{participant}/certificate', [ActivityParticipantController::class, 'certificate'])
            ->name('activities.participants.certificate')
            ->middleware('permission:activities.view');

        Route::get('reviews', [ReviewController::class, 'index'])
            ->name('reviews.index')
            ->middleware('permission:reviews.view');
        Route::post('reviews', [ReviewController::class, 'store'])
            ->name('reviews.store')
            ->middleware('permission:reviews.create');

        /*
        |--------------------------------------------------------------------------
        | Grupos y horarios
        |--------------------------------------------------------------------------
        */

        Route::resource('groups', AcademicGroupController::class)
            ->permissions($crud('groups'));
        Route::get('groups/{group}/schedule-pdf', [SchedulePrintController::class, 'group'])
            ->name('groups.schedule-pdf')
            ->middleware('permission:groups.view');

        // Horario propio del alumno (sin acceso a la gestión de grupos).
        Route::middleware('permission:my-schedule.view')->group(function () {
            Route::get('my-schedule', [MyScheduleController::class, 'index'])
                ->name('my-schedule.index');
            Route::get('my-schedule/{group}/pdf', [MyScheduleController::class, 'pdf'])
                ->name('my-schedule.pdf');
        });

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
            ->permissions($crud('students'));

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
            ->permissions($crud('knowledge-areas'));

        // Expediente propio del docente (sin acceso a los demás docentes).
        Route::get('my-record', [TeacherController::class, 'mine'])
            ->name('my-record')
            ->middleware('permission:my-record.view');
        Route::get('my-record/schedule-pdf', [TeacherController::class, 'minePdf'])
            ->name('my-record.schedule-pdf')
            ->middleware('permission:my-record.view');

        Route::get('teachers', [TeacherController::class, 'index'])
            ->name('teachers.index')
            ->middleware('permission:teachers.view');
        Route::get('teachers/{teacher}', [TeacherController::class, 'show'])
            ->name('teachers.show')
            ->middleware('permission:teachers.view');
        Route::get('teachers/{teacher}/schedule-pdf', [SchedulePrintController::class, 'teacher'])
            ->name('teachers.schedule-pdf')
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
