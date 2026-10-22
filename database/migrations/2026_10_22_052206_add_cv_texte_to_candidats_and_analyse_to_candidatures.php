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
        Schema::table('candidats', function (Blueprint $table): void {
            $table->text('cv_texte')->nullable()->after('cv_path');
        });

        Schema::table('candidatures', function (Blueprint $table): void {
            $table->json('analyse_preselection')->nullable()->after('date_candidature');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatures', function (Blueprint $table): void {
            $table->dropColumn('analyse_preselection');
        });

        Schema::table('candidats', function (Blueprint $table): void {
            $table->dropColumn('cv_texte');
        });
    }
};
