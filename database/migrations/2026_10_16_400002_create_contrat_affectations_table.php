<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_affectations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')
                ->constrained('embauche_employes')
                ->restrictOnDelete();
            $table->foreignId('poste_id')
                ->constrained('besoin_postes')
                ->restrictOnDelete();
            $table->foreignId('departement_id')
                ->constrained('besoin_departements')
                ->restrictOnDelete();
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->index(['employe_id', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_affectations');
    }
};
