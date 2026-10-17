<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `offre_emplois`.
 * Une offre naît d'UNE demande de recrutement validée (Bloc 1, table
 * `demande_recrutements`). ATTENTION : cette migration suppose que la table
 * du Bloc 1 existe déjà (préfixe 1 < préfixe 2, donc elle s'exécute avant).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offre_emplois', function (Blueprint $table) {
            $table->id();
            // Pas de cascadeOnDelete : on garde l'historique (règle de la TODOLIST).
            $table->foreignId('demande_recrutement_id')->constrained('demande_recrutements');
            $table->string('titre');
            $table->text('description')->nullable();
            $table->string('statut')->default('brouillon'); // enum StatutOffre
            $table->date('date_publication')->nullable();
            $table->date('date_cloture')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offre_emplois');
    }
};
