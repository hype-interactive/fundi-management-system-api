<?php

namespace App\Providers;

use App\Services\SmsService;
use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/../../config/sms.php' => config_path('sms.php'),
        ], 'sms-config');

        // Schedule SMS delivery status updates
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\UpdateSmsDeliveryStatuses::class,
                \App\Console\Commands\ProcessScheduledCampaigns::class,
            ]);
        }
    }
}
