<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('providers') && ! Schema::hasColumn('providers', 'registration_fee')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->decimal('registration_fee', 10, 2)->nullable()->after('status');
            });
        }

        // Backfill from legacy provider_fees "Registration" row when present.
        if (Schema::hasTable('provider_fees') && Schema::hasColumn('providers', 'registration_fee')) {
            $fees = DB::table('provider_fees')
                ->select('provider_slug', DB::raw('MIN(amount) as amount'))
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) like ?', ['%registration%'])
                        ->orWhereRaw('LOWER(name) = ?', ['registration']);
                })
                ->groupBy('provider_slug')
                ->get();

            foreach ($fees as $fee) {
                DB::table('providers')
                    ->where('provider_slug', $fee->provider_slug)
                    ->whereNull('registration_fee')
                    ->update(['registration_fee' => $fee->amount]);
            }
        }

        if (Schema::hasTable('clients') && Schema::hasColumn('clients', 'fee_id')) {
            try {
                Schema::table('clients', function (Blueprint $table) {
                    $table->dropIndex('clients_fee_idx');
                });
            } catch (\Throwable) {
                // Index may already be gone or named differently.
            }

            Schema::table('clients', function (Blueprint $table) {
                $table->dropColumn('fee_id');
            });
        }

        Schema::dropIfExists('provider_fees');
    }

    public function down(): void
    {
        if (! Schema::hasTable('provider_fees')) {
            Schema::create('provider_fees', function (Blueprint $table) {
                $table->id();
                $table->string('provider_slug');
                $table->string('name');
                $table->decimal('amount', 10, 2)->default(0);
                $table->timestamps();
                $table->index(['provider_slug', 'name'], 'provider_fees_provider_name_idx');
            });
        }

        if (Schema::hasTable('clients') && ! Schema::hasColumn('clients', 'fee_id')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('fee_id')->nullable()->after('registration_status');
                $table->index('fee_id', 'clients_fee_idx');
            });
        }

        if (Schema::hasTable('providers') && Schema::hasColumn('providers', 'registration_fee')) {
            Schema::table('providers', function (Blueprint $table) {
                $table->dropColumn('registration_fee');
            });
        }
    }
};
