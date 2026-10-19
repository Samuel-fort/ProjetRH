<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 3 — table `proposition_embauches`.
 * Colonnes du contrat d'interface (candidature_id, salaire, horaires, lieu_travail,
 * date_debut, statut, date_reponse) + 3 colonnes propres au Bloc 3 :
 *  - date_envoi         : quand la proposition a été envoyée ;
 *  - employe_id         : l'employé créé à l'acceptation (pratique pour le Bloc 4) ;
 *  - contrat_pdf_path   : chemin du PDF de contrat généré automatiquement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embauche_proposition_embauches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidature_id')->constrained('candidatures');
            $table->decimal('salaire', 12, 2);              // montant mensuel brut
            $table->string('horaires');                      // ex. "Lundi-vendredi, 8h-17h"
            $table->string('lieu_travail');
            $table->date('date_debut');
            $table->string('statut')->default('brouillon');  // enum StatutProposition
            $table->date('date_envoi')->nullable();
            $table->date('date_reponse')->nullable();
            $table->foreignId('employe_id')->nullable()->constrained('embauche_employes');
            $table->string('contrat_pdf_path')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embauche_proposition_embauches');
    }
};
