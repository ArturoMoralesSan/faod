<?php

namespace App\Providers;

use App\Models\LoginEvent;
use App\Models\User;
use App\View\Composers\MenuComposer;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\PendingResourceRegistration;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        /*
        |--------------------------------------------------------------------------
        | SuperAdmin bypass
        |--------------------------------------------------------------------------
        */

        /*
         * Permiso por acción en un Route::resource: ['index' => 'permission:x.view', ...].
         * (->middleware([...]) aplicaría todos los permisos a todas las rutas.)
         */
        PendingResourceRegistration::macro('permissions', function (array $map) {
            foreach ($map as $method => $middleware) {
                $this->middlewareFor($method, $middleware);
            }

            return $this;
        });

        Gate::before(function ($user, $ability) {
            // Solo cuando navega con el rol SuperAdmin (selector de rol).
            return method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()
                ? true
                : null;
        });

        /*
        |--------------------------------------------------------------------------
        | Menu Composer
        |--------------------------------------------------------------------------
        */

        View::composer(
            'app',
            MenuComposer::class
        );

        // Último acceso de cada usuario y bitácora de accesos.
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
                LoginEvent::record('login', $event->user);
            }
        });

        Event::listen(Logout::class, function (Logout $event) {
            if ($event->user instanceof User) {
                LoginEvent::record('logout', $event->user);
            }
        });

        Event::listen(Failed::class, function (Failed $event) {
            LoginEvent::record('failed', $event->user instanceof User ? $event->user : null, $event->credentials['email'] ?? null);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
                ? Password::min(12)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
                : null,
        );
    }
}
