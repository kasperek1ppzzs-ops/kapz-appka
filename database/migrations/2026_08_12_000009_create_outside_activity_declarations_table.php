<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outside_activity_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->string('status')->default('DRAFT'); // DRAFT, SUBMITTED, SIGNED
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();

            $table->unique(['kapz_id', 'reporting_period_id'], 'kapz_period_declaration_unique');
        });

        Schema::create('outside_activity_declaration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('declaration_id')->constrained('outside_activity_declarations')->cascadeOnDelete();
            $table->integer('order_num')->default(1);
            $table->string('person_type')->default('APZ'); // KAPZ, APZ
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('personal_number')->nullable();
            $table->string('full_name');
            $table->string('has_gainful_activity')->default('nie'); // nie, áno
            $table->string('is_funded_by_esif')->default('nie'); // nie, áno
            $table->string('contract_type')->nullable(); // Mandátna zmluva, Pracovná zmluva na skrátený úväzok, atď.
            $table->date('signature_date')->nullable();
            $table->string('signature_status')->nullable()->default('Podpísané');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outside_activity_declaration_items');
        Schema::dropIfExists('outside_activity_declarations');
    }
};
