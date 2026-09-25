<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Číselníky cestovného modulu prenesené z Excel matice:
 *  - km_limits: mesačné limity km podľa pôsobnosti (hárok „limity a prac. dni“, B4:C36),
 *  - travel_purposes: oficiálne účely ciest KAPZ (hárok GENERATOR, P14:R32) –
 *    `title` = krátky účel (stĺpec Q, Plán!H), `conclusion` = text „Závery/Odporúčania“ (stĺpec R).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('km_limits', function (Blueprint $table) {
            $table->id();
            $table->string('scope')->unique();
            $table->decimal('monthly_km', 8, 2);
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('travel_purposes', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('code');
            $table->string('role', 20)->default('KAPZ');
            $table->string('title');
            $table->text('conclusion')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['role', 'code']);
        });

        $now = now();

        $limits = [
            'Michalovce' => 535, 'Veľké Kapušany' => 300, 'Nitra' => 1365, 'Veľký Krtíš' => 1035,
            'Snina' => 650, 'Bardejov' => 420, 'Stará Ľubovňa' => 255, 'Humenné' => 385,
            'Vranov nad Topľou' => 240, 'Rimavská Sobota' => 970, 'Fiľakovo' => 340, 'Trebišov' => 930,
            'Gelnica' => 760, 'Prešov' => 520, 'Zvolen' => 1250, 'Poprad - okolie' => 745,
            'Spišská Nová Ves' => 525, 'Sabinov' => 245, 'Kežmarok' => 365, 'Košice-okolie' => 530,
            'Rožňava' => 455, 'Revúca' => 620, 'Košice' => 380, 'Poprad' => 400, 'Svidník' => 695,
            'Malacky' => 950, 'Senica' => 340, 'Veľký Šariš' => 340, 'Trhovište' => 440,
            'Nové Zámky' => 770, 'Ľubotín' => 275, 'Moldava nad Bodvou' => 390, 'Levoča' => 400,
        ];
        $rows = [];
        foreach ($limits as $scope => $km) {
            $rows[] = [
                'scope' => $scope,
                'monthly_km' => $km,
                'notes' => $scope === 'Veľký Krtíš' ? 'V matici zapísané ako „Velký Krtíš“.' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        // Nie je v matici – ponechané z predchádzajúcej verzie aplikácie, treba potvrdiť.
        $rows[] = [
            'scope' => 'Banská Bystrica',
            'monthly_km' => 1250,
            'notes' => 'Mimo Excel matice – hodnotu potvrdiť.',
            'created_at' => $now,
            'updated_at' => $now,
        ];
        DB::table('km_limits')->insert($rows);

        $purposes = [
            [1, 'Prezentácia NP zdravé komunity', 'Priama účasť Koordinátora asistentov podpory zdravia na pracovných stretnutiach týkajúcich sa prezentácie činnosti projektu.'],
            [2, 'Príprava a realizácia programov podpory zdravia', 'Priama účasť KAPZ pri príprave a samotnej realizácii programov podpory zdravia v marginalizovaných rómskych komunitách.'],
            [3, 'Kontrolná, podporná a hodnotiaca činnosť APZ', 'Kontrolná, organizačná, podporná a hodnotiaca činnosť APZ v súvislosti s výkonom ich pracovnej činnosti.'],
            [4, 'Osvetová činnosť', 'Priame vykonávanie osvetovej činnosti KAPZ v marginalizovaných rómskych komunitách, organizáciách, inštitúciách, školských zariadeniach všetkého typu.'],
            [5, 'Konzultácia a odborná príprava APZ', 'Odborná príprava, vedenie a konzultácia APZ pri plnení cieľov aktivít NP zdravé komunity.'],
            [6, 'Priama odborná podpora APZ v teréne', 'Podpora APZ pri riešení problémov a situácií s prácou APZ smerom k vedeniu miest a obcí, inštitúciám a úradom pri riešení nečakaných situácií v lokalitách'],
            [7, 'Pracovné stretnutie a konzultácie', 'Iniciácia alebo rozvíjanie spolupráce s ďalšími organizáciami, ktoré môžu byť nápomocné pri riešení problémov obyvateľov rómskych osád týkajúcich sa determinantov zdravia súvisiacimi s aktivitami projektu. Napríklad stretnutia s lekármi, stretnutia s regionálnymi manazermi podpory zdravia, stredným zdravotníckym personálom, RÚVZ, zamestnancami polikliník a nemocníc alebo pracovníkmi odborných sociálnych inštitúcií, ÚPSVaR , oddeleniami socialno –právnej ochrany detí a sociálnej kurately, MVO, účasť Koordinátora na ich workshopoch.'],
            [8, 'Odborné riadenie koordinačných stretnutí APZ', 'Pravidelné stretnutia KAPZ s APZ v rámci celej spádovej oblasti a riešenie a výmena informácii pri realizácii aktivít projektu v jednotlivých lokalitách.'],
            [9, 'Účasť na školení alebo spolupráca pri realizácii vzdelávania', 'Účasť KAPZ na školení alebo zabezpečenie účasti APZ na vzdelávacích aktivitách projektu (školenie, prednáška)'],
            [10, 'Komunikácia s médiami', 'Priama účasť Koordinátora asistentov podpory zdravia pri komunikácii s médiami.'],
            [11, 'Účasť na výberových konaniach a pohovoroch v rámci NP ZK', 'Účasť Koordinátora asistentov podpory zdravia na týchto výberových konaniach a osobných pohovoroch s APZ a KAPZ.'],
            [12, 'Pracovná porada s vedením organizácie v rámci NP ZK', 'Priama účasť Koordinátora asistentov podpory zdravia na pracovných poradách v ústredí organizácie alebo na určených miestach.'],
            [13, 'Pracovné stretnutie s Expertom pre terén', 'Priama účasť koordinátora asistentov podpory zdravia na pracovnom stretnutí s Expertom pre terén.'],
            [14, 'Riešenie aktuálnych a krízových situácií v teréne', 'Priama účasť Koordinátora asistentov podpory zdravia pri riešení krízových situácií v teréne. Za krízovú situáciu sa považuje, každý iný stav MRK v pridelenej lokalite, ako bežný.'],
            [15, 'Aktívne vyhľadávanie uchádzačov na pozíciu APZ v teréne', 'Priama pracovná aktivita koordinátora asistentov podpory zdravia vo vyhľadávaní potencionálnych uchádzačov na pozíciu APZ rámci NP ZK 2B v určených cieľových lokalitách'],
            [16, 'Pracovná návšteva v lokalitách', 'Osobná účasť Koordinátora asistentov podpory zdravia v priamom sledovaní komunity v lokalitách, zisťovanie informácií o situácii v lokalite a vykonávanie prieskumu zdravotného uvedomenia obyvateľov v marginalizovaných rómskych komunitách.'],
            [17, 'Vzájomná spolupráca s iným Koordinátorom asistentov podpory zdravia', 'Vzájomná spolupráca s iným koordinátorom( koordinátormi ) asistentov podpory zdravia pri zabezpečení cieľov a aktivít NP ZK 2B.'],
            [18, 'Riešenie aktuálnych a krízových situácií v teréne spojene s prevenciou pred covid 19', 'Priama účasť Koordinátora asistentov podpory zdravia pri riešení krízových situácií v teréne spojených s prevenciou alebo ochranou pred ochorením COVID-19.'],
            [19, 'Ďalšie aktivity spojene s realizáciou projektu', 'Ďalšie nešpecifikované vzniknuté udalosti, skutočnosti a situácie spojené s realizáciou projektu.'],
        ];
        DB::table('travel_purposes')->insert(array_map(fn ($p) => [
            'code' => $p[0],
            'role' => 'KAPZ',
            'title' => $p[1],
            'conclusion' => $p[2],
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ], $purposes));
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_purposes');
        Schema::dropIfExists('km_limits');
    }
};
