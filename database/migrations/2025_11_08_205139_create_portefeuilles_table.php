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
        Schema::create('portefeuilles', function (Blueprint $table) {
            $table->id();

            // Montant à créditer (dû à la boutique, livreur, admin, etc.)
            $table->integer('montant');

            // Le rôle qui reçoit l’argent (ex: boutique, livreur, admin)
            $table->string('role');

            // Identifiant de la commande liée
            $table->unsignedBigInteger('id_commande')->nullable();

            // Identifiant du bénéficiaire (optionnel mais utile)
            $table->unsignedBigInteger('id_beneficiaire')->nullable();

            // Statut du paiement : "En attente", "Réclamé", "Payé"
            $table->string('statut')->default('Réclamé');
            $table->boolean('is_paid')->default(false);

            // Date de paiement (si réglé)
            $table->timestamp('date_paiement')->nullable();

            $table->timestamps();

            // Relations
            $table->foreign('id_commande')
                ->references('id')
                ->on('commandes')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('portefeuilles');
    }
};
