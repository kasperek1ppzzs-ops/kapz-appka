<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('arrival_departure_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->onDelete('cascade');
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->onDelete('cascade');
            $table->string('person_type', 10); // KAPZ or APZ
            $table->unsignedBigInteger('person_id'); // kapz_id or apz_id
            $table->string('personal_number', 50)->nullable();
            $table->string('full_name', 150);
            $table->string('location', 150)->nullable();
            $table->string('project_code', 50)->default('401405DUQ8');
            $table->string('approver_name', 150)->nullable();
            $table->string('status', 20)->default('DRAFT'); // DRAFT, SUBMITTED, APPROVED
            $table->timestamps();

            $table->unique(['kapz_id', 'reporting_period_id', 'person_type', 'person_id'], 'unique_book_person_period');
        });

        Schema::create('arrival_departure_book_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('arrival_departure_books')->onDelete('cascade');
            $table->unsignedTinyInteger('day_number'); // 1 to 31
            $table->date('record_date');
            $table->string('arrival_hour', 10)->nullable();
            $table->string('arrival_minute', 10)->nullable();
            $table->string('departure_hour', 10)->nullable();
            $table->string('departure_minute', 10)->nullable();
            $table->string('break_departure_hour', 10)->nullable();
            $table->string('break_departure_minute', 10)->nullable();
            $table->string('break_arrival_hour', 10)->nullable();
            $table->string('break_arrival_minute', 10)->nullable();
            $table->string('break_reason', 150)->nullable();
            $table->string('visited_location', 255)->nullable();
            $table->string('approved_by', 150)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamps();

            $table->index(['book_id', 'day_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('arrival_departure_book_items');
        Schema::dropIfExists('arrival_departure_books');
    }
};
