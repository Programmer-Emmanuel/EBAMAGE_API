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
        Schema::create('soldes', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['admin', 'boutique']);
            $table->unsignedBigInteger('id_admin')->nullable();
            $table->unsignedBigInteger('id_btq')->nullable();
            $table->decimal('montant', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('id_admin')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('id_btq')->references('id')->on('boutiques')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soldes');
    }
};
