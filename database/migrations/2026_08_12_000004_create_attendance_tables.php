<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_kapz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->date('date');
            $table->string('workplace')->nullable();
            $table->string('status')->default('work'); // 'work', 'holiday', 'pn', 'nv', 'ocr', 'travel', 'weekend', 'other'
            $table->decimal('hours_worked', 4, 2)->default(7.50);
            $table->decimal('overtime_hours', 4, 2)->default(0.00);
            $table->text('activity_description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['kapz_id', 'reporting_period_id', 'date']);
        });

        Schema::create('attendance_apz', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apz_id')->constrained('apz_profiles')->cascadeOnDelete();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete(); // Coordinator at the time
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->date('date');
            $table->string('workplace')->nullable();
            $table->string('status')->default('work');
            $table->decimal('hours_worked', 4, 2)->default(7.50);
            $table->text('activity_description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['apz_id', 'reporting_period_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_apz');
        Schema::dropIfExists('attendance_kapz');
    }
};
