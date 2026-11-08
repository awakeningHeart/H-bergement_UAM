<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Interface anglaise (P2) : versions anglaises facultatives des contenus saisis ; vide = texte français affiché.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('types_chambre', function (Blueprint $table) {
            $table->string('libelle_en')->nullable()->after('libelle');
            $table->json('equipements_en')->nullable()->after('equipements');
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->string('adresse_en')->nullable()->after('adresse');
            $table->text('description_en')->nullable()->after('description');
        });

        Schema::table('chambres', function (Blueprint $table) {
            $table->text('description_en')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('types_chambre', fn (Blueprint $table) => $table->dropColumn(['libelle_en', 'equipements_en']));
        Schema::table('sites', fn (Blueprint $table) => $table->dropColumn(['adresse_en', 'description_en']));
        Schema::table('chambres', fn (Blueprint $table) => $table->dropColumn('description_en'));
    }
};
