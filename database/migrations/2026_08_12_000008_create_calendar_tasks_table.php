<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assigned_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('kapz_id')->nullable()->constrained('kapz_profiles')->nullOnDelete();
            $table->string('target_scope')->nullable(); // e.g. 'Banská Bystrica', 'VŠETCI'
            $table->date('task_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->default('EXPERT_DIRECTIVE'); // EXPERT_DIRECTIVE, CONTROL, DEADLINE, TRAINING, NOTE
            $table->string('priority')->default('MEDIUM'); // URGENT, HIGH, MEDIUM, LOW
            $table->string('status')->default('PENDING'); // PENDING, IN_PROGRESS, COMPLETED
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_tasks');
    }
};
