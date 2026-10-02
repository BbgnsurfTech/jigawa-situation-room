<?php

namespace App\Providers;

use App\Models\PollingUnitAgent;
use App\Models\WardReport;
use App\Observers\PollingUnitAgentObserver;
use App\Observers\WardReportObserver;
use Illuminate\Support\ServiceProvider;

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
        WardReport::observe(WardReportObserver::class);
        PollingUnitAgent::observe(PollingUnitAgentObserver::class);
    }
}
