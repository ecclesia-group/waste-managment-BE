<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Client;
use App\Models\DistrictAssembly;
use App\Models\Facility;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Notification actor_type values map to model classes (lowercase keys).
        Relation::morphMap([
            'admin' => Admin::class,
            'client' => Client::class,
            'district_assembly' => DistrictAssembly::class,
            'facility' => Facility::class,
            'provider' => Provider::class,
        ]);
    }
}
