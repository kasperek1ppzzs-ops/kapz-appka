<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kapz_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('personal_number')->unique();
            $table->string('full_name');
            $table->string('scope')->nullable(); // pôsobnosť / lokalita
            $table->string('region_expert')->nullable(); // Príslušnosť k Expertovi pre terén (Mgr. Ľudmila Grešková, atď.)
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('apz_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('personal_number')->unique();
            $table->string('full_name');
            $table->string('scope')->nullable(); // pôsobnosť / komunita
            $table->string('position')->nullable(); // pracovná pozícia
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apz_profiles');
        Schema::dropIfExists('kapz_profiles');
    }
};
