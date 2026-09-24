<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('travel_plans', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_plans', 'km_limit')) {
                $table->decimal('km_limit', 8, 2)->default(500.00)->after('status');
            }
        });

        Schema::table('travel_plan_items', function (Blueprint $table) {
            if (!Schema::hasColumn('travel_plan_items', 'departure_time')) {
                $table->string('departure_time', 10)->default('08:00')->after('trip_date');
            }
            if (!Schema::hasColumn('travel_plan_items', 'arrival_time')) {
                $table->string('arrival_time', 10)->default('16:00')->after('departure_time');
            }
        });
    }

    public function down(): void
    {
        Schema::table('travel_plan_items', function (Blueprint $table) {
            $table->dropColumn(['departure_time', 'arrival_time']);
        });

        Schema::table('travel_plans', function (Blueprint $table) {
            $table->dropColumn('km_limit');
        });
    }
};
