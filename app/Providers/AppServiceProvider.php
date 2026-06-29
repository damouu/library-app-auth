<?php

namespace App\Providers;

use App\Contracts\Clock;
use App\Contracts\PasswordVerifier;
use App\Kafka\EventPublisher;
use App\Kafka\KafkaEventPublisher;
use App\Services\AuthenticationService;
use App\Services\UtcClock;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(
            EventPublisher::class,
            KafkaEventPublisher::class
        );

        $this->app->singleton(
            PasswordVerifier::class,
            AuthenticationService::class
        );

        $this->app->singleton(
            Clock::class,
            UtcClock::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
