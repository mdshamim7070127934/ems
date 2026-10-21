<?php

namespace App\Providers;

use App\Enums\Mailbox\MailboxPermission;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\DevCommands;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureMailboxPermissions();
        $this->configureDevelopmentQueueWorker();
    }

    protected function configureMailboxPermissions(): void
    {
        foreach (MailboxPermission::cases() as $permission) {
            Gate::define($permission->value, fn (User $user): bool => true);
        }
    }

    protected function configureDevelopmentQueueWorker(): void
    {
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return;
        }

        DevCommands::except('queue');
        DevCommands::artisan(
            'queue:listen --tries=1 --timeout=0 --queue=emails,email-sync,default',
            'queues',
        );
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
