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
        Schema::create('besoin_demande_recrutements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('poste_id')
                  ->constrained('besoin_postes')
                  ->restrictOnDelete();
            $table->foreignId('departement_id')
                  ->constrained('besoin_departements')
                  ->restrictOnDelete();
            $table->foreignId('demandeur_id')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->unsignedInteger('nombre')->default(1);
            $table->string('motif');
            $table->decimal('budget', 12, 2)->nullable();
            $table->date('date_souhaitee')->nullable();
            $table->string('statut')->default('brouillon');
            $table->timestamps();
        });
    }
    
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('besoin_demande_recrutements');
    }
};
