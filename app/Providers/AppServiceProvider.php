<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Filament\Support\Facades\FilamentTimezone;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SocialiteProviders\Authelia\Provider as AutheliaProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Route names are first-registration-wins, and Fortify names its own
        // login route 'login' when it boots — before the app's route files
        // load. Claiming the name here, in the register phase, is the only
        // spot that beats it. It must point at the Member door (SSO), never
        // the break-glass one.
        Route::view('login', 'pages::auth.login')->name('login');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('authelia', AutheliaProvider::class);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        // Store timestamps in UTC (app timezone) but display/enter them as Eastern in
        // Filament — date pickers and columns render in this zone; the DB stays UTC.
        FilamentTimezone::set('America/Louisville');

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
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
