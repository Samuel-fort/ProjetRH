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
        Schema::create('besoin_departements', function (Blueprint $table) {
           $table->id();
           $table->string('nom')->unique();
           $table->string('code')->nullable()->unique();
           $table->foreignId('parent_id')
                 ->nullable()
                 ->constrained('besoin_departements')
                 ->restrictOnDelete();
           $table->timestamps();
       });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('besoin_departements');
    }
};
