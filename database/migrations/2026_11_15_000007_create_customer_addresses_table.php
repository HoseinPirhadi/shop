<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 80);
            $table->string('recipient_name', 160);
            $table->string('phone', 30);
            $table->char('country_code', 2);
            $table->string('state', 120)->nullable();
            $table->string('city', 120);
            $table->string('address_line1', 255);
            $table->string('address_line2', 255)->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'is_default']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');
    }
};
