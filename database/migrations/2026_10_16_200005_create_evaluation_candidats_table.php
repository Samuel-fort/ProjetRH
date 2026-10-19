<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bloc 2 — table `evaluation_candidats` : notes et commentaires.
 * `entretien_id` est nullable : on peut évaluer un dossier sans entretien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluation_candidats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidature_id')->constrained('candidatures');
            $table->foreignId('entretien_id')->nullable()->constrained('entretiens');
            $table->foreignId('evaluateur_id')->constrained('users');
            $table->decimal('note', 4, 2);           // note sur 20, ex. 14.50
            $table->text('commentaire')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluation_candidats');
    }
};
