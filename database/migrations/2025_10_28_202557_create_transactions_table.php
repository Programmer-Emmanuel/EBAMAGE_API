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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->enum('type', ['user_payment', 'admin_to_boutique', 'admin_to_livreur']);
            $table->unsignedBigInteger('id_admin')->nullable();
            $table->unsignedBigInteger('id_btq')->nullable();
            $table->unsignedBigInteger('id_clt')->nullable();
            $table->unsignedBigInteger('id_commande')->nullable();

            $table->string('reference')->unique();
            $table->string('code_paiement')->nullable()->unique(); // ✅ le code que l’utilisateur recevra (ex: PAY-1234)

            $table->enum('provider', ['mtn', 'moov', 'orange', 'wave'])->nullable();
            $table->enum('channel', ['mobile_money', 'card'])->default('mobile_money');

            $table->decimal('montant', 12, 2);
            $table->decimal('commission_admin', 12, 2)->default(0);
            $table->decimal('montant_net', 12, 2)->default(0);

            $table->enum('statut', ['pending', 'success', 'failed'])->default('pending');
            $table->timestamp('date_transaction')->nullable();

            $table->timestamps();

            $table->foreign('id_admin')->references('id')->on('admins')->onDelete('cascade');
            $table->foreign('id_btq')->references('id')->on('boutiques')->onDelete('cascade');
            $table->foreign('id_clt')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('id_commande')->references('id')->on('commandes')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
