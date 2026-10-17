<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `candidatures` : le LIEN entre un candidat et une offre.
 * Un candidat peut avoir plusieurs candidatures (offres différentes),
 * mais pas deux fois la même offre -> contrainte d'unicité composée.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offre_emploi_id')->constrained('offre_emplois');
            $table->foreignId('candidat_id')->constrained('candidats');
            $table->string('statut')->default('recue'); // enum StatutCandidature
            $table->date('date_candidature');
            $table->timestamps();

            $table->unique(['offre_emploi_id', 'candidat_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatures');
    }
};
