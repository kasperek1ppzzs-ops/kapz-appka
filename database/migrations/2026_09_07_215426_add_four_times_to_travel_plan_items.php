<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rozšírenie z 2 časov (departure_time, arrival_time) na 4 časy podľa Excel matice:
 * 1. departure_from_base_time  = Odchod z východiskovej lokality
 * 2. arrival_at_dest_time      = Príchod do cieľovej lokality
 * 3. departure_from_dest_time  = Odchod z cieľovej lokality
 * 4. arrival_at_base_time      = Príchod do východiskovej lokality späť
 *
 * Staré stĺpce departure_time a arrival_time zostávajú pre spätnu kompatibilitu
 * a sú namapované na body 1 a 4 (celkový rozsah cesty).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_plan_items', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_plan_items', 'arrival_at_dest_time')) {
                $table->string('arrival_at_dest_time', 10)->nullable()->after('departure_time');
            }
            if (!Schema::hasColumn('travel_plan_items', 'departure_from_dest_time')) {
                $table->string('departure_from_dest_time', 10)->nullable()->after('arrival_at_dest_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_plan_items', function (Blueprint $table) {
            $table->dropColumn(['arrival_at_dest_time', 'departure_from_dest_time']);
        });
    }
};
