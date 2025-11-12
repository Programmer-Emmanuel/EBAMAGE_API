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
        Schema::create('corresponds', function (Blueprint $table) {
            $table->unsignedBigInteger('id_article');
            $table->unsignedBigInteger('id_categorie');

            $table->foreign('id_article')
                ->references('id')
                ->on('articles')
                ->onDelete('cascade');

            $table->foreign('id_categorie')
                ->references('id')
                ->on('categories')
                ->onDelete('cascade');

            $table->primary(['id_article', 'id_categorie']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corresponds');
    }
};
