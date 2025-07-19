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
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('id_clt');
            $table->unsignedBigInteger('id_btq');
            $table->unsignedBigInteger('id_ville');
            $table->unsignedBigInteger('id_commune');

            $table->json('articles'); 
            $table->integer('quantite');
            $table->integer('prix');
            $table->integer('livraison')->default(1000);
            $table->integer('prix_total');
            $table->string('statut')->default('En attente');
            $table->string('quartier');
            $table->tinyInteger('moyen_de_paiement')->default(1);

            $table->timestamps();

            $table->foreign('id_clt')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_btq')->references('id')->on('boutiques')->onDelete('cascade');
            $table->foreign('id_ville')->references('id')->on('villes')->onDelete('cascade');
            $table->foreign('id_commune')->references('id')->on('communes')->onDelete('cascade');
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
