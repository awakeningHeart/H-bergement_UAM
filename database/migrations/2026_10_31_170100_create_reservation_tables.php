<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_codes', function (Blueprint $table) {
            $table->id();
            $table->string('telephone', 20);
            $table->string('code_hash');
            $table->timestamp('expire_le');
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('envoye_le');
            $table->timestamp('utilise_le')->nullable();
            $table->index(['telephone', 'expire_le']);
        });

        Schema::create('chambre_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chambre_id')->constrained('chambres')->cascadeOnDelete();
            $table->string('telephone', 20)->nullable();
            $table->date('arrivee');
            $table->date('depart');
            $table->timestamp('expire_le');
            $table->string('token', 64)->unique();
            $table->timestamps();
            $table->index(['chambre_id', 'expire_le']);
        });

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('chambre_id')->constrained('chambres');
            $table->foreignId('organisme_id')->nullable()->constrained('organismes');
            $table->enum('motif', ['mission', 'autres']);
            $table->dateTime('arrivee');
            $table->dateTime('depart');
            $table->unsignedSmallInteger('nuitees');
            $table->string('correspondant_nom')->nullable();
            $table->string('correspondant_tel', 20)->nullable();
            $table->enum('payeur', ['client', 'organisme'])->default('client');
            $table->unsignedBigInteger('montant_ht')->default(0);
            $table->unsignedBigInteger('montant_tva')->default(0);
            $table->unsignedBigInteger('montant_ttc')->default(0);
            $table->enum('statut', ['en_attente', 'confirmee', 'refusee', 'expiree', 'annulee', 'en_cours', 'terminee', 'non_presente'])->default('en_attente');
            $table->string('jeton_suivi_hash', 64)->nullable()->index();
            $table->string('idempotence_cle', 64)->nullable()->unique();
            $table->text('signature')->nullable();
            $table->timestamp('conditions_acceptees_le')->nullable();
            $table->timestamps();
            $table->index(['chambre_id', 'arrivee', 'depart']);
            $table->index('statut');
        });

        Schema::create('reservation_repas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
            $table->enum('repas', ['petit_dejeuner', 'dejeuner', 'diner']);
            $table->unsignedSmallInteger('quantite');
            $table->unsignedInteger('prix_unitaire');
        });

        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('reservation_id')->constrained('reservations');
            $table->enum('type', ['facture', 'avoir'])->default('facture');
            $table->foreignId('facture_origine_id')->nullable()->constrained('factures');
            $table->bigInteger('montant_ht');
            $table->bigInteger('montant_tva');
            $table->bigInteger('montant_ttc');
            $table->timestamp('emise_le');
            $table->string('pdf_path')->nullable();
            $table->timestamps();
        });

        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('reservations');
            $table->foreignId('facture_id')->nullable()->constrained('factures');
            $table->unsignedBigInteger('montant');
            $table->enum('mode', ['especes', 'virement', 'cheque', 'mobile_money']);
            $table->string('reference')->nullable();
            $table->foreignId('recu_par')->nullable()->constrained('users');
            $table->timestamp('paye_le');
            $table->timestamps();
        });
    }
};
