<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bins', function (Blueprint $table) {
            $table->id();
            $table->string('bin_slug')->unique();
            $table->string('bin_code')->unique();
            $table->string('client_slug');
            $table->string('provider_slug');
            $table->string('product_slug')->nullable();
            $table->string('source')->default('registration'); // registration|purchase|manual
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_slug', 'status'], 'bins_client_status_idx');
            $table->index(['provider_slug', 'status'], 'bins_provider_status_idx');
        });

        Schema::create('provider_zones', function (Blueprint $table) {
            $table->id();
            $table->string('provider_slug');
            $table->string('zone_slug');
            $table->timestamp('assigned_at')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['provider_slug', 'zone_slug'], 'provider_zones_unique');
        });

        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('bulk_waste_requests', 'amount')) {
                $table->decimal('amount', 10, 2)->nullable()->after('status');
            }
        });

        $now = now();

        DB::table('clients')
            ->whereNotNull('bin_code')
            ->orderBy('id')
            ->get(['client_slug', 'provider_slug', 'bin_code'])
            ->each(function ($client) use ($now) {
                DB::table('bins')->updateOrInsert(
                    ['bin_code' => $client->bin_code],
                    [
                        'bin_slug' => (string) \Illuminate\Support\Str::uuid(),
                        'client_slug' => $client->client_slug,
                        'provider_slug' => $client->provider_slug,
                        'source' => 'registration',
                        'status' => 'active',
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            });

        if (Schema::hasTable('provider_zone_assignments')) {
            DB::table('provider_zone_assignments')
                ->orderBy('id')
                ->get(['provider_slug', 'zone_slug', 'assigned_at', 'status'])
                ->each(function ($assignment) use ($now) {
                    DB::table('provider_zones')->updateOrInsert(
                        ['provider_slug' => $assignment->provider_slug, 'zone_slug' => $assignment->zone_slug],
                        [
                            'assigned_at' => $assignment->assigned_at,
                            'status' => $assignment->status ?? 'active',
                            'updated_at' => $now,
                            'created_at' => $now,
                        ]
                    );
                });

            Schema::dropIfExists('provider_zone_assignments');
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_zone_assignments')) {
            Schema::create('provider_zone_assignments', function (Blueprint $table) {
                $table->id();
                $table->string('provider_slug');
                $table->string('zone_slug');
                $table->timestamp('assigned_at')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();

                $table->unique(['provider_slug', 'zone_slug']);
            });

            DB::table('provider_zones')
                ->orderBy('id')
                ->get(['provider_slug', 'zone_slug', 'assigned_at', 'status'])
                ->each(function ($assignment) {
                    DB::table('provider_zone_assignments')->insert([
                        'provider_slug' => $assignment->provider_slug,
                        'zone_slug' => $assignment->zone_slug,
                        'assigned_at' => $assignment->assigned_at,
                        'status' => $assignment->status ?? 'active',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }

        Schema::table('bulk_waste_requests', function (Blueprint $table) {
            if (Schema::hasColumn('bulk_waste_requests', 'amount')) {
                $table->dropColumn('amount');
            }
        });

        Schema::dropIfExists('provider_zones');
        Schema::dropIfExists('bins');
    }
};
