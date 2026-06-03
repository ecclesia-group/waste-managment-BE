<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('zones', function (Blueprint $table) {
            if (! Schema::hasColumn('zones', 'district_assembly_slug')) {
                $table->string('district_assembly_slug')->nullable()->after('region');
                $table->index('district_assembly_slug');
            }
        });

        Schema::create('suburbs', function (Blueprint $table) {
            $table->id();
            $table->string('suburb_slug')->unique();
            $table->string('district_assembly_slug')->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('suburb_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignId('suburb_id')->constrained('suburbs')->cascadeOnDelete();
            $table->string('zone_slug')->index();
            $table->timestamps();
            $table->unique(['suburb_id', 'zone_slug']);
        });

        Schema::table('clients', function (Blueprint $table) {
            if (! Schema::hasColumn('clients', 'zone_slug')) {
                $table->string('zone_slug')->nullable()->after('group_slug');
                $table->index(['provider_slug', 'zone_slug'], 'clients_provider_zone_idx');
            }
        });

        Schema::table('drivers', function (Blueprint $table) {
            if (! Schema::hasColumn('drivers', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('status');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
                $table->timestamp('last_location_at')->nullable()->after('longitude');
                $table->index(['provider_slug', 'latitude'], 'drivers_provider_lat_idx');
            }
        });

        Schema::table('route_planners', function (Blueprint $table) {
            if (! Schema::hasColumn('route_planners', 'route_meta')) {
                $table->json('route_meta')->nullable()->after('status');
            }
        });

        // Schema::table('route_planner_bin_assignments', function (Blueprint $table) {
        //     if (! Schema::hasColumn('route_planner_bin_assignments', 'stop_order')) {
        //         $table->unsignedInteger('stop_order')->nullable()->after('pickup_code');
        //         $table->unsignedSmallInteger('eta_minutes')->nullable()->after('stop_order');
        //     }
        // });

        Schema::create('pickup_scan_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug')->index();
            $table->string('pickup_code')->index();
            $table->string('idempotency_key')->unique();
            $table->timestamp('device_scanned_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_scan_events');
        // Schema::table('route_planner_bin_assignments', function (Blueprint $table) {
        //     if (Schema::hasColumn('route_planner_bin_assignments', 'stop_order')) {
        //         $table->dropColumn(['stop_order', 'eta_minutes']);
        //     }
        // });
        Schema::table('route_planners', function (Blueprint $table) {
            if (Schema::hasColumn('route_planners', 'route_meta')) {
                $table->dropColumn('route_meta');
            }
        });
        Schema::table('drivers', function (Blueprint $table) {
            if (Schema::hasColumn('drivers', 'latitude')) {
                $table->dropIndex('drivers_provider_lat_idx');
                $table->dropColumn(['latitude', 'longitude', 'last_location_at']);
            }
        });
        Schema::table('clients', function (Blueprint $table) {
            if (Schema::hasColumn('clients', 'zone_slug')) {
                $table->dropIndex('clients_provider_zone_idx');
                $table->dropColumn('zone_slug');
            }
        });
        Schema::dropIfExists('suburb_zone');
        Schema::dropIfExists('suburbs');
        Schema::table('zones', function (Blueprint $table) {
            if (Schema::hasColumn('zones', 'district_assembly_slug')) {
                $table->dropIndex(['district_assembly_slug']);
                $table->dropColumn('district_assembly_slug');
            }
        });
    }
};
