<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_onboarding_taches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('onboarding_id')
                ->constrained('contrat_onboardings')
                ->restrictOnDelete();
            $table->string('code');
            $table->string('libelle');
            $table->string('statut')->default('a_faire');
            $table->foreignId('responsable_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->date('date_echeance')->nullable();
            $table->timestamp('date_realisation')->nullable();
            $table->timestamps();

            $table->unique(['onboarding_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_onboarding_taches');
    }
};
