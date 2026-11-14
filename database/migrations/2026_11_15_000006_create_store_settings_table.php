<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('store_name', 160)->nullable();
            $table->char('currency_code', 3)->nullable();
            $table->timestamps();
        });

        DB::table('store_settings')->insert([
            'id' => 1,
            'store_name' => null,
            'currency_code' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};
