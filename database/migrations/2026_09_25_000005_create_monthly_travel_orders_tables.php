<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesačný cestovný príkaz podľa hárku „Cestovný príkaz“ (1 CP na KAPZ a mesiac,
 * číslo MM/RRRR/INICIÁLY/ZK) a jeho úseky – tabuľka vyúčtovania R82:AE268.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kapz_profiles', function (Blueprint $table) {
            $table->string('initials', 10)->nullable()->after('full_name');        // HLASENIE!B42
            $table->string('residence_address')->nullable()->after('address');    // CP D9
            $table->string('residence_city')->nullable()->after('residence_address'); // CP G9
            $table->string('vehicle_plate', 20)->nullable()->after('residence_city'); // CP H48
        });

        Schema::create('monthly_travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->foreignId('travel_plan_id')->nullable()->constrained('travel_plans')->nullOnDelete();
            $table->string('order_number');
            $table->string('status', 20)->default('DRAFT');          // DRAFT, COMPLETED
            $table->string('residence_address')->nullable();         // 2. Bydlisko
            $table->string('residence_city')->nullable();
            $table->string('companions')->nullable();                // 3. Spolucestujúci
            $table->string('vehicle')->nullable();                   // 4. Určený dopravný prostriedok
            $table->string('vehicle_plate', 20)->nullable();
            $table->decimal('expected_costs', 10, 2)->nullable();    // 5. Predpokladaná čiastka výdajov
            $table->decimal('advance_amount', 10, 2)->default(0);    // 6. Povolená záloha / Preddavok (AD270)
            $table->decimal('fuel_consumption', 6, 2)->nullable();   // Spotreba AUV l/100 km (AI83)
            $table->date('report_submitted_on')->nullable();         // 7. Správa podaná dňa
            $table->boolean('meals_free')->nullable();               // Stravovanie poskytnuté bezplatne
            $table->boolean('accommodation_free')->nullable();       // Ubytovanie poskytnuté bezplatne
            $table->boolean('discounted_ticket')->nullable();        // Voľný – zľavnený cestovný lístok
            $table->text('note')->nullable();                        // 9. Poznámka
            $table->timestamps();

            $table->unique(['kapz_id', 'reporting_period_id']);
        });

        Schema::create('monthly_travel_order_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_travel_order_id')->constrained('monthly_travel_orders')->cascadeOnDelete();
            $table->foreignId('travel_plan_segment_id')->nullable()->constrained('travel_plan_segments')->nullOnDelete();
            $table->date('trip_date');                               // R
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('from_place');                            // T (Odchod)
            $table->string('departure_time', 10)->nullable();        // U
            $table->string('to_place');                              // T (Príchod)
            $table->string('arrival_time', 10)->nullable();          // U
            $table->string('transport_mode', 10)->default('AUV');    // V
            $table->decimal('km', 8, 2)->default(0);                 // W
            $table->string('work_time', 30)->nullable();             // X – začiatok a koniec pracovného výkonu
            $table->string('purpose')->nullable();                   // účel (z plánu) pre súhrn a správu
            $table->decimal('fuel_price', 6, 3)->nullable();         // AI – cena PH za daný týždeň
            $table->decimal('amortization', 10, 2)->default(0);      // Z
            $table->decimal('meals', 10, 2)->default(0);             // AA – stravné
            $table->decimal('accommodation_cost', 10, 2)->default(0); // AB – nocľažné
            $table->decimal('other_costs', 10, 2)->default(0);       // AC – nutné vedľajšie výdavky
            $table->decimal('adjusted', 10, 2)->nullable();          // AE – upravené
            $table->timestamps();

            $table->index(['monthly_travel_order_id', 'trip_date', 'position'], 'mto_segments_order_date_pos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_travel_order_segments');
        Schema::dropIfExists('monthly_travel_orders');
        Schema::table('kapz_profiles', function (Blueprint $table) {
            $table->dropColumn(['initials', 'residence_address', 'residence_city', 'vehicle_plate']);
        });
    }
};
