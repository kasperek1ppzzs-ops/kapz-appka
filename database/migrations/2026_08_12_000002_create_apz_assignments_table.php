<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apz_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apz_id')->constrained('apz_profiles')->cascadeOnDelete();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable(); // null means active assignment
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['apz_id', 'valid_from', 'valid_to']);
            $table->index(['kapz_id', 'valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apz_assignments');
    }
};
