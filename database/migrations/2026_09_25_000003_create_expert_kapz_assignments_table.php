<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historizované pridelenie KAPZ Expertovi pre terén (rovnaký princíp ako apz_assignments).
 * Expert vidí a schvaľuje iba KAPZ, ktorí mu sú pridelení k danému dňu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expert_kapz_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expert_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_to')->nullable(); // null = aktívne pridelenie
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['expert_user_id', 'valid_from', 'valid_to']);
            $table->index(['kapz_id', 'valid_from', 'valid_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expert_kapz_assignments');
    }
};
