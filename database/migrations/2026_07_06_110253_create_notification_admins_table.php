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
        Schema::create('notification_admins', function (Blueprint $table) {
            $table->id();

            // L’utilisateur ou la boutique destinataire
            $table->foreignId('admin_id')->nullable()->constrained('admins')->onDelete('cascade');

            $table->string('title');
            $table->text('message');
            $table->string('type')->default(null);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_admins');
    }
};
