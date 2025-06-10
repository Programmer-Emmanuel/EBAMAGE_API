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
        Schema::create('livreurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom_liv');
            $table->string('pren_liv');
            $table->string('email_liv')->unique();
            $table->string('tel_liv', 10)->unique();
            $table->string('photo_liv');
            $table->string('photo_cni');
            $table->string('photo_permis');
            $table->string('password_liv');
            $table->boolean('is_active')->default(false);
            $table->integer('solde_tdl');
            $table->string('code_otp')->nullable();
            $table->timestamp('otp_expires_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_verify')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('livreurs');
    }
};
