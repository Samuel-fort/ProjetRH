<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `entretiens` : étapes planifiées d'une candidature
 * (sélection sur dossier, entretien, test).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entretiens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidature_id')->constrained('candidatures');
            $table->string('type');                 // enum TypeEtape
            $table->dateTime('date_heure');
            $table->string('lieu')->nullable();
            // L'intervenant est un utilisateur interne (manager, RH...).
            $table->foreignId('intervenant_id')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entretiens');
    }
};
