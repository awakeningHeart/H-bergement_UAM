<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->text('refus_motif')->nullable()->after('statut');
            // Remise des clés (check-in) : point de départ du délai de paiement de 48 h (RG07).
            $table->timestamp('cles_remises_le')->nullable()->after('refus_motif');
            $table->timestamp('depart_effectif_le')->nullable()->after('cles_remises_le');
        });

        Schema::table('factures', function (Blueprint $table) {
            // Photographie du document à l'émission (RG12 : la facture ne change plus).
            $table->json('donnees')->nullable()->after('emise_le');
            $table->text('motif_avoir')->nullable()->after('donnees');
        });
    }
};
