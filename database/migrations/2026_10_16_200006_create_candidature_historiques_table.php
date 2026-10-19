<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `candidature_historiques` : TRAÇABILITÉ.
 * Une ligne à chaque changement de statut d'une candidature (qui, quand, de quoi à quoi).
 * Table "journal" : on n'y écrit qu'en ajout, jamais de modification -> pas de updated_at.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidature_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidature_id')->constrained('candidatures');
            $table->string('ancien_statut')->nullable(); // null à la création de la candidature
            $table->string('nouveau_statut');
            $table->foreignId('user_id')->nullable()->constrained('users'); // null si action système/publique
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidature_historiques');
    }
};
