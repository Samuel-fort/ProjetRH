<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `candidats`.
 * Un CANDIDAT est une personne (≠ Employé). Il existe UNE seule fois,
 * même s'il postule à plusieurs offres : l'email est donc unique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidats', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('prenom');
            $table->string('email')->unique();      // sert à retrouver un candidat existant
            $table->string('telephone')->nullable();
            $table->string('cv_path')->nullable();   // chemin du CV dans storage (GED)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidats');
    }
};
