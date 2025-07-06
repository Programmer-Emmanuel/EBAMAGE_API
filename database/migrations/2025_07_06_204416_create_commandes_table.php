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

            // Déclaration des colonnes d'abord
            $table->unsignedBigInteger('id_clt');
            $table->unsignedBigInteger('id_btq');
            $table->unsignedBigInteger('id_article');
            $table->unsignedBigInteger('id_commune');

            $table->integer('quantite');
            $table->string('statut');
            $table->string('quartier');

            $table->timestamps();

            $table->foreign('id_clt')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('id_btq')
                ->references('id')
                ->on('boutiques')
                ->onDelete('cascade');

            $table->foreign('id_article')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');

            $table->foreign('id_commune')
                ->references('id')
                ->on('communes')
                ->onDelete('cascade');
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
