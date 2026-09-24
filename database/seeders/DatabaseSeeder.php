<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\KapzProfile;
use App\Models\ApzProfile;
use App\Models\ApzAssignment;
use App\Models\ReportingPeriod;
use App\Models\AttendanceKapz;
use App\Models\AttendanceApz;
use App\Models\TravelPlan;
use App\Models\TravelPlanItem;
use App\Models\TravelOrder;
use App\Models\TravelReport;
use App\Models\TravelExpense;
use App\Models\Statement;
use App\Models\ActivityReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::create([
            'name' => 'Administrátor Systému',
            'email' => 'admin@kapz.sk',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'personal_number' => 'ADMIN-001',
            'scope' => 'Ústredie',
            'phone' => '+421 900 000 000',
        ]);

        // 2. KAPZ User 1
        $userKapz1 = User::create([
            'name' => 'Mgr. Peter Novák',
            'email' => 'novak@kapz.sk',
            'password' => Hash::make('password'),
            'role' => 'kapz',
            'personal_number' => 'KAPZ-001',
            'scope' => 'Banská Bystrica',
            'phone' => '+421 901 111 222',
        ]);

        $profileKapz1 = KapzProfile::create([
            'user_id' => $userKapz1->id,
            'personal_number' => 'KAPZ-001',
            'full_name' => 'Mgr. Peter Novák',
            'scope' => 'Banská Bystrica',
            'region_expert' => 'Mgr. Ľudmila Grešková',
            'phone' => '+421 901 111 222',
            'email' => 'novak@kapz.sk',
            'address' => 'Námestie SNP 14, 974 01 Banská Bystrica',
            'is_active' => true,
        ]);

        // KAPZ User 2
        $userKapz2 = User::create([
            'name' => 'PhDr. Mária Horváthová',
            'email' => 'horvathova@kapz.sk',
            'password' => Hash::make('password'),
            'role' => 'kapz',
            'personal_number' => 'KAPZ-002',
            'scope' => 'Košice',
            'phone' => '+421 902 333 444',
        ]);

        $profileKapz2 = KapzProfile::create([
            'user_id' => $userKapz2->id,
            'personal_number' => 'KAPZ-002',
            'full_name' => 'PhDr. Mária Horváthová',
            'scope' => 'Košice',
            'region_expert' => 'Mgr. Peter Vágner',
            'phone' => '+421 902 333 444',
            'email' => 'horvathova@kapz.sk',
            'address' => 'Hlavná 42, 040 01 Košice',
            'is_active' => true,
        ]);

        // 3. APZ Profiles
        $apz1 = ApzProfile::create([
            'personal_number' => 'APZ-101',
            'full_name' => 'Ján Balog',
            'scope' => 'Podlavice',
            'position' => 'Asistent APZ',
            'phone' => '+421 911 222 333',
            'email' => 'balog@apz.sk',
            'address' => 'Podlavická 10, Banská Bystrica',
            'is_active' => true,
        ]);

        $apz2 = ApzProfile::create([
            'personal_number' => 'APZ-102',
            'full_name' => 'Elena Berkyová',
            'scope' => 'Šalková',
            'position' => 'Senior APZ',
            'phone' => '+421 912 333 444',
            'email' => 'berkyova@apz.sk',
            'address' => 'Šalkovská 5, Banská Bystrica',
            'is_active' => true,
        ]);

        $apz3 = ApzProfile::create([
            'personal_number' => 'APZ-103',
            'full_name' => 'Milan Danihel',
            'scope' => 'Brezno',
            'position' => 'Asistent APZ',
            'phone' => '+421 913 444 555',
            'email' => 'danihel@apz.sk',
            'address' => 'Námestie gen. Štefánika 3, Brezno',
            'is_active' => true,
        ]);

        $apz4 = ApzProfile::create([
            'personal_number' => 'APZ-104',
            'full_name' => 'Monika Harvanová',
            'scope' => 'Telgárt',
            'position' => 'Asistent APZ',
            'phone' => '+421 914 555 666',
            'email' => 'harvanova@apz.sk',
            'address' => 'Telgárt 45',
            'is_active' => true,
        ]);

        $apz5 = ApzProfile::create([
            'personal_number' => 'APZ-105',
            'full_name' => 'Róbert Pokoš',
            'scope' => 'Valaská',
            'position' => 'Asistent APZ',
            'phone' => '+421 915 666 777',
            'email' => 'pokos@apz.sk',
            'address' => 'Valaská 12',
            'is_active' => true,
        ]);

        $apz6 = ApzProfile::create([
            'personal_number' => 'APZ-106',
            'full_name' => 'Katarína Bartošová',
            'scope' => 'Šumiac',
            'position' => 'Asistent APZ',
            'phone' => '+421 916 777 888',
            'email' => 'bartosova@apz.sk',
            'address' => 'Šumiac 88',
            'is_active' => true,
        ]);

        $apz7 = ApzProfile::create([
            'personal_number' => 'APZ-107',
            'full_name' => 'Peter Lovaš',
            'scope' => 'Pohorelá',
            'position' => 'Asistent APZ',
            'phone' => '+421 917 888 999',
            'email' => 'lovas@apz.sk',
            'address' => 'Pohorelá 34',
            'is_active' => true,
        ]);

        $apz8 = ApzProfile::create([
            'personal_number' => 'APZ-108',
            'full_name' => 'Zdenka Šarközyová',
            'scope' => 'Čierny Balog',
            'position' => 'Asistent APZ',
            'phone' => '+421 918 999 000',
            'email' => 'sarkozyova@apz.sk',
            'address' => 'Čierny Balog 112',
            'is_active' => true,
        ]);

        $apz9 = ApzProfile::create([
            'personal_number' => 'APZ-109',
            'full_name' => 'Marek Cibuľa',
            'scope' => 'Heľpa',
            'position' => 'Asistent APZ',
            'phone' => '+421 919 111 222',
            'email' => 'cibula@apz.sk',
            'address' => 'Heľpa 76',
            'is_active' => true,
        ]);

        $apz10 = ApzProfile::create([
            'personal_number' => 'APZ-110',
            'full_name' => 'Silvia Berkyová',
            'scope' => 'Závadka nad Hronom',
            'position' => 'Asistent APZ',
            'phone' => '+421 920 222 333',
            'email' => 'silvia.berkyova@apz.sk',
            'address' => 'Závadka nad Hronom 23',
            'is_active' => true,
        ]);

        // 4. Assignments
        $kapz1Apzs = [$apz1, $apz2, $apz3, $apz4, $apz5, $apz6, $apz7, $apz8, $apz9, $apz10];
        foreach ($kapz1Apzs as $a) {
            ApzAssignment::create([
                'apz_id' => $a->id,
                'kapz_id' => $profileKapz1->id,
                'valid_from' => '2026-01-01',
                'valid_to' => null,
                'notes' => 'Priradenie pre obvod Banská Bystrica a Horehronie',
            ]);
        }

        // 5. Reporting Periods
        $periodAug = ReportingPeriod::create([
            'year' => 2026,
            'month' => 8,
            'is_closed' => false,
        ]);

        $periodSep = ReportingPeriod::create([
            'year' => 2026,
            'month' => 9,
            'is_closed' => false,
        ]);

        // 6. Seed Attendance for August 2026
        for ($day = 1; $day <= 31; $day++) {
            $dateStr = sprintf('2026-08-%02d', $day);
            $dayOfWeek = date('N', strtotime($dateStr));
            $isWeekend = ($dayOfWeek >= 6);

            // KAPZ 1 Attendance
            AttendanceKapz::create([
                'kapz_id' => $profileKapz1->id,
                'reporting_period_id' => $periodAug->id,
                'date' => $dateStr,
                'workplace' => $isWeekend ? null : 'Banská Bystrica',
                'status' => $isWeekend ? 'weekend' : 'work',
                'hours_worked' => $isWeekend ? 0.00 : 7.50,
                'overtime_hours' => 0.00,
                'activity_description' => $isWeekend ? null : 'Terénna práca a administratíva v regióne Banská Bystrica',
            ]);

            // Seed attendance for all KAPZ 1 APZs
            foreach ($kapz1Apzs as $itemApz) {
                AttendanceApz::create([
                    'apz_id' => $itemApz->id,
                    'kapz_id' => $profileKapz1->id,
                    'reporting_period_id' => $periodAug->id,
                    'date' => $dateStr,
                    'workplace' => $isWeekend ? null : $itemApz->scope,
                    'status' => $isWeekend ? 'weekend' : 'work',
                    'hours_worked' => $isWeekend ? 0.00 : 7.50,
                    'activity_description' => $isWeekend ? null : 'Zdravotná mediácia a osveta v komunite ' . $itemApz->scope,
                ]);
            }
        }

        // 7. Seed Travel Plan & Workflow Items for August 2026
        $travelPlan1 = TravelPlan::create([
            'kapz_id' => $profileKapz1->id,
            'reporting_period_id' => $periodAug->id,
            'title' => 'Mesačný plán pracovných ciest - August 2026',
            'status' => 'SUBMITTED',
            'version' => 1,
            'submitted_at' => '2026-08-01 08:30:00',
        ]);

        $item1 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 1,
            'trip_date' => '2026-08-04',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Brezno',
            'purpose' => 'Kontrola výkonu zdravotnej mediácie a metodická podpora APZ',
            'transport_mode' => 'AAuto',
            'target_apz_id' => $apz1->id,
            'estimated_km' => 42.00,
        ]);

        $item2 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 2,
            'trip_date' => '2026-08-10',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Podlavice',
            'purpose' => 'Kontrola výkonu zdravotnej mediácie a podpora APZ Ján Balog',
            'transport_mode' => 'AAuto',
            'target_apz_id' => $apz1->id,
            'estimated_km' => 24.50,
        ]);

        $item3 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 2,
            'trip_date' => '2026-08-12',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Brezno',
            'purpose' => 'Koordinačné stretnutie so samosprávou a pediatrom',
            'transport_mode' => 'VHD',
            'target_apz_id' => $apz1->id,
            'estimated_km' => 42.00,
        ]);

        $item4 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 3,
            'trip_date' => '2026-08-18',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Šalková',
            'purpose' => 'Konzultácia s terénnymi pracovníkmi a osvetová prednáška',
            'transport_mode' => 'AAuto',
            'target_apz_id' => $apz2->id,
            'estimated_km' => 18.00,
        ]);

        $item5 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 3,
            'trip_date' => '2026-08-20',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Telgárt',
            'purpose' => 'Distribúcia zdravotníckeho a osvetového materiálu',
            'transport_mode' => 'PAuto',
            'target_apz_id' => $apz1->id,
            'estimated_km' => 88.00,
        ]);

        $item6 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 4,
            'trip_date' => '2026-08-25',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Brezno',
            'purpose' => 'Kontrola výkonu zdravotnej mediácie a podpora APZ Ján Balog',
            'transport_mode' => 'AAuto',
            'target_apz_id' => $apz1->id,
            'estimated_km' => 42.00,
        ]);

        $item7 = TravelPlanItem::create([
            'travel_plan_id' => $travelPlan1->id,
            'week_number' => 4,
            'trip_date' => '2026-08-27',
            'departure_location' => 'Banská Bystrica',
            'destination_location' => 'Valaská',
            'purpose' => 'Metodické usmernenie a riešenie individuálnych potrieb komunity',
            'transport_mode' => 'AAuto',
            'target_apz_id' => $apz2->id,
            'estimated_km' => 34.00,
        ]);

        // Travel Order for Item 2
        $order1 = TravelOrder::create([
            'kapz_id' => $profileKapz1->id,
            'travel_plan_item_id' => $item2->id,
            'order_number' => 'CP-2026-08-001',
            'departure_datetime' => '2026-08-10 08:00:00',
            'arrival_datetime' => '2026-08-10 16:30:00',
            'departure_place' => 'Banská Bystrica',
            'destination_place' => 'Podlavice',
            'purpose' => 'Kontrola výkonu zdravotnej mediácie a podpora APZ Ján Balog',
            'transport_means' => 'Služobné motorové vozidlo (AAuto)',
            'advance_amount' => 0.00,
            'status' => 'COMPLETED',
        ]);

        TravelReport::create([
            'travel_order_id' => $order1->id,
            'kapz_id' => $profileKapz1->id,
            'report_date' => '2026-08-10',
            'summary_of_activities' => 'Zrealizovaná kontrola dochádzky a výkonu mediácie u APZ Ján Balog.',
            'outcomes' => 'Práca APZ prebieha v súlade s mesačným plánom. Boli prerokované individuálne potreby komunity.',
            'issues_encountered' => 'Bez zistených závažných nedostatkov.',
        ]);

        TravelExpense::create([
            'travel_order_id' => $order1->id,
            'kapz_id' => $profileKapz1->id,
            'total_km' => 24.50,
            'rate_per_km' => 0.252,
            'total_km_compensation' => 6.17,
            'diem_compensation' => 8.60,
            'accommodation_costs' => 0.00,
            'other_expenses' => 0.00,
            'advance_deducted' => 0.00,
            'final_balance' => 14.77,
        ]);

        // 8. Statements (Prehlásenie o činnosti mimo pracovného pomeru) and Activity Reports
        $decl = \App\Models\OutsideActivityDeclaration::create([
            'kapz_id' => $profileKapz1->id,
            'reporting_period_id' => $periodAug->id,
            'status' => 'SIGNED',
            'signed_at' => '2026-08-31 16:00:00',
        ]);

        $order = 1;
        // KAPZ row
        \App\Models\OutsideActivityDeclarationItem::create([
            'declaration_id' => $decl->id,
            'order_num' => $order++,
            'person_type' => 'KAPZ',
            'person_id' => $profileKapz1->id,
            'personal_number' => $profileKapz1->personal_number,
            'full_name' => $profileKapz1->full_name,
            'has_gainful_activity' => 'nie',
            'is_funded_by_esif' => 'nie',
            'contract_type' => null,
            'signature_date' => '2026-08-31',
            'signature_status' => null,
        ]);

        // APZ rows
        $sampleApzs = $profileKapz1->assignedApzsForDate('2026-08-15');
        foreach ($sampleApzs as $idx => $apz) {
            $hasGainful = ($idx === 4 || $idx === 7) ? 'áno' : 'nie';
            $contract = ($idx === 4) ? 'Mandátna zmluva' : (($idx === 7) ? 'Pracovná zmluva na skrátený pracovný úväzok' : null);

            \App\Models\OutsideActivityDeclarationItem::create([
                'declaration_id' => $decl->id,
                'order_num' => $order++,
                'person_type' => 'APZ',
                'person_id' => $apz->id,
                'personal_number' => $apz->personal_number,
                'full_name' => $apz->full_name,
                'has_gainful_activity' => $hasGainful,
                'is_funded_by_esif' => 'nie',
                'contract_type' => $contract,
                'signature_date' => '2026-08-31',
                'signature_status' => null,
            ]);
        }

        Statement::create([
            'kapz_id' => $profileKapz1->id,
            'reporting_period_id' => $periodAug->id,
            'statement_type' => 'PREHLASENIE',
            'title' => 'Čestné prehlásenie k výkazu dochádzky a pracovných ciest',
            'body_content' => 'Prehlasujem na svoju česť, že všetky údaje uvedené v evidencii dochádzky a správe z pracovných ciest za mesiac August 2026 sú pravdivé a úplné.',
            'signed_at' => '2026-08-31 16:00:00',
        ]);

        ActivityReport::create([
            'kapz_id' => $profileKapz1->id,
            'reporting_period_id' => $periodAug->id,
            'summary_text' => 'Pravidelná mesačná správa o činnosti KAPZ pre obvod Banská Bystrica.',
            'metrics_json' => [
                'total_hours' => 176,
                'total_km' => 42.5,
                'apz_count' => 2,
                'completed_mediations' => 84,
            ],
        ]);

        // Seed KNIHY (Kniha príchodov a odchodov pre KAPZ + celý tím APZ)
        $bookService = new \App\Services\ArrivalDepartureBookService();
        $bookService->getAllTeamBooks($profileKapz1, $periodAug);

        // 9. Contacts Seed from matrix (Zoznam kontaktov)
        $contactsJsonPath = base_path('scratch/exact_contacts_by_department.json');
        if (file_exists($contactsJsonPath)) {
            $contactsData = json_decode(file_get_contents($contactsJsonPath), true);
            foreach ($contactsData as $c) {
                \App\Models\Contact::create([
                    'section' => $c['section'] ?? 'Koordinátori asistentov podpory zdravia',
                    'order_num' => is_numeric($c['order_num']) ? (int)$c['order_num'] : null,
                    'name' => $c['name'],
                    'email' => $c['email'] ?: null,
                    'phone' => $c['phone'] ?: null,
                    'scope' => $c['scope'] ?: null,
                    'position' => $c['position'] ?: 'Asistent podpory zdravia',
                    'region_expert' => $c['region_expert'] ?: null,
                ]);
            }
        }

        // 10. Calendar Tasks & Directives from Expert pre terén
        \App\Models\CalendarTask::create([
            'assigned_by_user_id' => $admin->id,
            'kapz_id' => $profileKapz1->id,
            'task_date' => '2026-08-04',
            'start_time' => '09:00:00',
            'end_time' => '13:00:00',
            'title' => 'Metodická kontrola terénneho výkonu v Brezne',
            'description' => 'Overenie evidencie dochádzky u APZ a preverenie priebehu očkovacieho plánu.',
            'category' => 'EXPERT_DIRECTIVE',
            'priority' => 'HIGH',
            'status' => 'COMPLETED',
            'completed_at' => '2026-08-04 14:00:00',
            'completion_note' => 'Kontrola zrealizovaná bez zistených nedostatkov.',
        ]);

        \App\Models\CalendarTask::create([
            'assigned_by_user_id' => $admin->id,
            'kapz_id' => $profileKapz1->id,
            'task_date' => '2026-08-12',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'title' => 'Koordinačné stretnutie s pediatrami a starostom (Brezno)',
            'description' => 'Riešenie nízkej zaočkovanosti v lokalite a dohoda na harmonograme osvety.',
            'category' => 'EXPERT_DIRECTIVE',
            'priority' => 'URGENT',
            'status' => 'IN_PROGRESS',
        ]);

        \App\Models\CalendarTask::create([
            'assigned_by_user_id' => $admin->id,
            'kapz_id' => $profileKapz1->id,
            'task_date' => '2026-08-20',
            'start_time' => '08:30:00',
            'end_time' => '15:00:00',
            'title' => 'Distribúcia zdravotníckeho a osvetového materiálu (Telgárt)',
            'description' => 'Doručenie materiálu APZ Monika Harvanová a poučenie o evidencii výdaja.',
            'category' => 'CONTROL',
            'priority' => 'MEDIUM',
            'status' => 'PENDING',
        ]);

        \App\Models\CalendarTask::create([
            'assigned_by_user_id' => $admin->id,
            'kapz_id' => null,
            'target_scope' => 'VŠETCI',
            'task_date' => '2026-08-28',
            'start_time' => '08:00:00',
            'end_time' => '16:00:00',
            'title' => 'Uzávierka a odovzdanie mesačných výkazov dochádzky a ciest',
            'description' => 'Celosystémový termín na odovzdanie podpísaných mesačných výkazov dochádzky a vyúčtovaní za August 2026.',
            'category' => 'DEADLINE',
            'priority' => 'URGENT',
            'status' => 'PENDING',
        ]);
    }
}
