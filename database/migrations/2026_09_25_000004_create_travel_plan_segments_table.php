<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Úseky ciest podľa hárku „Plán pracovných ciest“: každý úsek = dvojica riadkov
 * Odchod (miesto, čas) / Príchod (miesto, čas) so stĺpcami 3–8
 * (dopravný prostriedok, km, účel, stručný opis, ubytovanie, spolucestujúca osoba).
 * Položka plánu (travel_plan_items) = jeden deň; jej súhrnné polia sa odvodzujú z úsekov.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_plan_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_plan_item_id')->constrained('travel_plan_items')->cascadeOnDelete();
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('from_place');
            $table->string('departure_time', 10)->nullable();
            $table->string('to_place');
            $table->string('arrival_time', 10)->nullable();
            $table->string('transport_mode', 10)->default('AUV');
            $table->decimal('km', 8, 2)->default(0);
            $table->string('purpose')->nullable();       // stĺpec 5 – účel z číselníka (GENERATOR)
            $table->text('description')->nullable();     // stĺpec 6 – stručný opis
            $table->string('accommodation', 50)->nullable(); // stĺpec 7 – ubytovanie (Nie/Áno)
            $table->string('companions')->nullable();    // stĺpec 8 – spolucestujúca osoba
            $table->timestamps();

            $table->index(['travel_plan_item_id', 'position']);
        });

        // Existujúce položky → úseky (tam / späť, pri okruhu reťaz cez všetky zastávky).
        foreach (DB::table('travel_plan_items')->orderBy('id')->get() as $item) {
            $stops = array_values(array_filter(array_map('trim', preg_split('/[,;+]+/', (string) $item->destination_location))));
            if (!$stops) {
                continue;
            }
            $base = $item->departure_location;
            $chain = array_merge([$base], $stops, [$base]);
            $count = count($chain) - 1;
            $totalKm = (float) $item->estimated_km;
            $firstKm = $count === 2 ? round($totalKm / 2, 2) : $totalKm;

            for ($i = 0; $i < $count; $i++) {
                $isFirst = $i === 0;
                $isLast = $i === $count - 1;
                DB::table('travel_plan_segments')->insert([
                    'travel_plan_item_id' => $item->id,
                    'position' => $i + 1,
                    'from_place' => $chain[$i],
                    'departure_time' => $isFirst ? $item->departure_time : ($isLast ? ($item->departure_from_dest_time ?? null) : null),
                    'to_place' => $chain[$i + 1],
                    'arrival_time' => $isFirst ? ($item->arrival_at_dest_time ?? null) : ($isLast ? $item->arrival_time : null),
                    'transport_mode' => $item->transport_mode === 'AAuto' ? 'AUS' : ($item->transport_mode === 'VHD' ? 'A' : $item->transport_mode),
                    'km' => $isFirst ? $firstKm : ($count === 2 ? round($totalKm - $firstKm, 2) : 0),
                    'purpose' => $isFirst ? $item->purpose : null,
                    'accommodation' => 'Nie',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_plan_segments');
    }
};
