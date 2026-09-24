<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Texty správ k jednotlivým dňom cestovného príkazu:
 *  - report_text: „Správa z pracovnej cesty“ (hárok 19, B13) – vlastný text KAPZ,
 *  - conclusion: „Závery/Odporúčania“ (hárok GENERATOR, E16) – ak je prázdne,
 *    použije sa text podľa účelu z číselníka travel_purposes.
 * Riadok vzniká až pri prvom uložení; bez neho sa použijú predvolené texty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_travel_order_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_travel_order_id')->constrained('monthly_travel_orders')->cascadeOnDelete();
            $table->date('trip_date');
            $table->text('report_text')->nullable();
            $table->text('conclusion')->nullable();
            $table->timestamps();

            $table->unique(['monthly_travel_order_id', 'trip_date'], 'mto_days_order_date_unique');
        });

        Schema::table('monthly_travel_orders', function (Blueprint $table) {
            $table->string('vehicle_model')->nullable()->after('vehicle_plate'); // Správa z PC B21 – typ vozidla
        });
    }

    public function down(): void
    {
        Schema::table('monthly_travel_orders', function (Blueprint $table) {
            $table->dropColumn('vehicle_model');
        });
        Schema::dropIfExists('monthly_travel_order_days');
    }
};
