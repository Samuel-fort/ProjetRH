<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('besoin_demande_historiques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_recrutement_id')
                  ->constrained('besoin_demande_recrutements')
                  ->restrictOnDelete();
            $table->string('ancien_statut')->nullable();
            $table->string('nouveau_statut');
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('besoin_demande_historiques');
    }
};
