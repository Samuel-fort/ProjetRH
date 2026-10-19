<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
    {
        Schema::create('besoin_validations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demande_recrutement_id')
                  ->constrained('besoin_demande_recrutements')
                  ->restrictOnDelete();
            $table->foreignId('validateur_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('etape');
            $table->string('statut')->default('en_attente');
            $table->text('commentaire')->nullable();
            $table->timestamp('date_decision')->nullable();
            $table->timestamps();
    
            $table->unique(['demande_recrutement_id', 'etape']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('besoin_validations');
    }
};
