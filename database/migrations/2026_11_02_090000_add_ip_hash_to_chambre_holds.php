<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chambre_holds', function (Blueprint $table) {
            // Empreinte de l'adresse IP du demandeur : plafonne le nombre de chambres bloquées par visiteur.
            $table->string('ip_hash', 64)->nullable()->after('telephone')->index();
        });
    }
};
