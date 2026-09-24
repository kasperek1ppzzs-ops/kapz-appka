<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->string('statement_type')->default('PREHLASENIE');
            $table->string('title');
            $table->longText('body_content');
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kapz_id')->constrained('kapz_profiles')->cascadeOnDelete();
            $table->foreignId('reporting_period_id')->constrained('reporting_periods')->cascadeOnDelete();
            $table->text('summary_text')->nullable();
            $table->json('metrics_json')->nullable();
            $table->timestamps();
        });

        Schema::create('pdf_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type'); // EVIDENCIA_KAPZ, EVIDENCIA_APZ, KNIHY, PLAN_CESTY, CESTOVNY_PRIKAZ, SPRAVA_Z_CESTY, VYUTOVANIE, PREHLASENIE
            $table->unsignedBigInteger('reference_id');
            $table->integer('version')->default(1);
            $table->string('file_path');
            $table->foreignId('generated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata_json')->nullable();
            $table->timestamps();

            $table->index(['document_type', 'reference_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('pdf_documents');
        Schema::dropIfExists('activity_reports');
        Schema::dropIfExists('statements');
    }
};
