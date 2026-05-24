<?php

namespace App\Providers;

use App\Events\NotificationCreated;
use App\Listeners\CheckEscalation;
use App\Listeners\UpdateSubscriberStats;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event; // <-- Add this
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

        // Register listeners sequentially to guarantee execution order:
        // 1. Stats update runs first
        Event::listen(NotificationCreated::class, UpdateSubscriberStats::class);
        
        // 2. Escalation check runs second
        Event::listen(NotificationCreated::class, CheckEscalation::class);
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
