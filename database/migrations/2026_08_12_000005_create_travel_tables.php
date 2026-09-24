<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('DRAFT'); // 'DRAFT', 'SUBMITTED', 'APPROVED', 'RETURNED', 'REJECTED'
            $table->integer('version')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('travel_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_plan_id')->constrained('travel_plans')->cascadeOnDelete();
            $table->unsignedTinyInteger('week_number')->default(1); // 1, 2, 3, 4, 5 (Týždeň v mesiaci)
            $table->date('trip_date');
            $table->string('departure_location');
            $table->string('destination_location');
            $table->text('purpose');
            $table->string('transport_mode')->default('AAuto'); // AAuto, VHD, etc.
            $table->foreignId('target_apz_id')->nullable()->constrained('apz_profiles')->nullOnDelete();
            $table->decimal('estimated_km', 8, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('travel_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('travel_plan_item_id')->nullable()->constrained('travel_plan_items')->nullOnDelete();
            $table->string('order_number')->unique();
            $table->dateTime('departure_datetime');
            $table->dateTime('arrival_datetime')->nullable();
            $table->string('departure_place');
            $table->string('destination_place');
            $table->text('purpose');
            $table->string('transport_means');
            $table->decimal('advance_amount', 8, 2)->default(0.00);
            $table->string('status')->default('CREATED'); // CREATED, IN_PROGRESS, COMPLETED
            $table->timestamps();
        });

        Schema::create('travel_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained('travel_orders')->cascadeOnDelete();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->date('report_date');
            $table->text('summary_of_activities');
            $table->text('outcomes')->nullable();
            $table->text('issues_encountered')->nullable();
            $table->timestamps();
        });

        Schema::create('travel_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('travel_order_id')->constrained('travel_orders')->cascadeOnDelete();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->decimal('total_km', 8, 2)->default(0.00);
            $table->decimal('rate_per_km', 6, 3)->default(0.252);
            $table->decimal('total_km_compensation', 10, 2)->default(0.00);
            $table->decimal('diem_compensation', 10, 2)->default(0.00);
            $table->decimal('accommodation_costs', 10, 2)->default(0.00);
            $table->decimal('other_expenses', 10, 2)->default(0.00);
            $table->decimal('advance_deducted', 10, 2)->default(0.00);
            $table->decimal('final_balance', 10, 2)->default(0.00);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_expenses');
        Schema::dropIfExists('travel_reports');
        Schema::dropIfExists('travel_orders');
        Schema::dropIfExists('travel_plan_items');
        Schema::dropIfExists('travel_plans');
    }
};
