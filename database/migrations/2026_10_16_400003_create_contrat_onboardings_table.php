<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_onboardings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')
                ->unique()
                ->constrained('embauche_employes')
                ->restrictOnDelete();
            $table->foreignId('contrat_id')
                ->constrained('contrat_contrats')
                ->restrictOnDelete();
            $table->string('statut')->default('en_cours');
            $table->date('date_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_onboardings');
    }
};
