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
        Schema::create('besoin_postes', function (Blueprint $table) {
            $table->id();
            $table->string('intitule');
            $table->text('description')->nullable();
            $table->foreignId('departement_id')
              ->constrained('besoin_departements')
              ->restrictOnDelete();
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->unique(['departement_id', 'intitule']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('besoin_postes');
    }
};
