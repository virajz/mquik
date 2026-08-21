<?php

namespace App\Providers;

use App\Models\User;
use App\Support\AppSettings;
use App\Support\Otp\MockOtpSender;
use App\Support\Otp\OtpSender;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Mocked for now. Swapping in a real gateway is this one binding — and
        // a live sender returns null, which is what stops codes being shown.
        $this->app->bind(OtpSender::class, MockOtpSender::class);

        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Applied before the session middleware runs, so an admin-set idle
        // timeout takes effect without touching .env. Falls back to the
        // config/session.php default when nothing is saved.
        if ($minutes = AppSettings::int('security.session_lifetime')) {
            config(['session.lifetime' => $minutes]);
        }

        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Super Admin bypass: a user with the "Super Admin" role passes every gate.
     * Use sparingly — most permission grants should go through Spatie's role/permission tables.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Super Admin') ? true : null);
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
