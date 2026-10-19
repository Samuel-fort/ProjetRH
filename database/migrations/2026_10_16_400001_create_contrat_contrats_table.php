<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contrat_contrats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employe_id')
                ->constrained('embauche_employes')
                ->restrictOnDelete();
            $table->string('type');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->decimal('salaire', 12, 2);
            $table->string('horaires');
            $table->string('lieu_travail');
            $table->string('statut')->default('cree');
            $table->date('date_signature')->nullable();
            $table->date('date_validation')->nullable();
            $table->foreignId('contrat_precedent_id')
                ->nullable()
                ->unique()
                ->constrained('contrat_contrats')
                ->restrictOnDelete();
            $table->unsignedBigInteger('proposition_embauche_id')->nullable()->unique();
            $table->timestamps();

            $table->index(['employe_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contrat_contrats');
    }
};
