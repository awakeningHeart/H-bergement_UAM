<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('villes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('slug')->unique();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ville_id')->constrained('villes');
            $table->string('nom');
            $table->string('slug')->unique();
            $table->string('adresse')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('description')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ville_id', 'actif']);
        });

        Schema::create('types_chambre', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->unsignedSmallInteger('capacite')->default(1);
            $table->json('equipements')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('chambres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites');
            $table->foreignId('type_chambre_id')->constrained('types_chambre');
            $table->string('numero', 20);
            $table->string('etage', 20)->nullable();
            $table->text('description')->nullable();
            $table->boolean('actif')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['site_id', 'numero']);
        });

        Schema::create('tarifs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('type_chambre_id')->nullable()->constrained('types_chambre');
            $table->foreignId('chambre_id')->nullable()->constrained('chambres');
            $table->unsignedInteger('prix_nuit');
            $table->date('date_debut');
            $table->date('date_fin')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['type_chambre_id', 'date_debut']);
            $table->index(['chambre_id', 'date_debut']);
        });

        Schema::create('tarifs_repas', function (Blueprint $table) {
            $table->id();
            $table->enum('repas', ['petit_dejeuner', 'dejeuner', 'diner'])->unique();
            $table->unsignedInteger('prix');
            $table->timestamps();
        });

        Schema::create('organismes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('adresse')->nullable();
            $table->string('telephone', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('contact')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('telephone', 20)->unique();
            $table->string('nom');
            $table->string('prenom');
            $table->string('type_piece')->nullable();
            $table->string('numero_piece')->nullable();
            $table->date('piece_delivree_le')->nullable();
            $table->string('email')->nullable();
            $table->string('titre_fonction')->nullable();
            $table->timestamps();
        });

        Schema::create('parametres', function (Blueprint $table) {
            $table->string('cle')->primary();
            $table->json('valeur')->nullable();
            $table->timestamps();
        });

        Schema::create('sequences', function (Blueprint $table) {
            $table->id();
            $table->string('cle');
            $table->unsignedSmallInteger('annee');
            $table->unsignedBigInteger('valeur')->default(0);
            $table->timestamps();
            $table->unique(['cle', 'annee']);
        });

        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            $table->morphs('photoable');
            $table->string('chemin');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });

        Schema::create('site_user', function (Blueprint $table) {
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->primary(['site_id', 'user_id']);
        });
    }
};
