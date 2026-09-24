<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Zjednotenie kódu „Náhradné voľno“: formulár APZ ukladal `substitute_leave`,
 * formulár KAPZ `nv`. Pozri App\Enums\AttendanceStatus.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('attendance_apz')->where('status', 'substitute_leave')->update(['status' => 'nv']);
        DB::table('attendance_kapz')->where('status', 'substitute_leave')->update(['status' => 'nv']);
    }

    public function down(): void
    {
        // Pôvodný kód nie je možné spätne rozlíšiť – zmena je bezpečne nevratná.
    }
};
