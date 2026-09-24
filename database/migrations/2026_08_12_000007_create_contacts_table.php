<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->string('section')->default('Koordinátori asistentov podpory zdravia'); // Oddelenie / Sekcia
            $table->unsignedInteger('order_num')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('scope')->nullable(); // Pôsobnosť / lokalita
            $table->string('position')->default('Asistent podpory zdravia');
            $table->string('region_expert')->nullable(); // Príslušnosť k Expertovi pre terén
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
