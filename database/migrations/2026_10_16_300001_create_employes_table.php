<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 3 — table `employes`.
 * Rappel de conception : CANDIDAT ≠ EMPLOYÉ. Un employé est une fiche distincte,
 * créée à l'acceptation d'une proposition, qui garde un lien vers le candidat d'origine.
 * Pas de colonne `type_contrat` : l'historique des contrats vit dans la table `contrats` (Bloc 4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('embauche_employes', function (Blueprint $table) {
            $table->id();
            // Nullable : un employé importé n'a jamais été candidat.
            $table->foreignId('candidat_id')->nullable()->constrained('candidats');
            // Nullable jusqu'à l'attribution ; unique (plusieurs NULL sont autorisés par la base).
            $table->string('matricule')->nullable()->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();
            $table->string('statut')->default('en_integration'); // enum StatutEmploye
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embauche_employes');
    }
};
