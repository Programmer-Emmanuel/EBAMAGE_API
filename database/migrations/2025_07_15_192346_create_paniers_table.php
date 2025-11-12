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
        Schema::create('paniers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_clt')->nullable();
            $table->unsignedBigInteger('id_article')->nullable();
            $table->integer('quantite')->nullable();
            $table->integer('prix_total')->nullable();
            $table->json('variations')->nullable();
            $table->timestamps();


            $table->foreign('id_clt')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->foreign('id_article')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('paniers');
    }
};
