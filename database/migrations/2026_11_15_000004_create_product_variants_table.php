<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\DB;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('name', 160)->default('پیش‌فرض');
            $table->string('sku', 100)->nullable()->unique();
            $table->json('options')->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->boolean('track_inventory')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'is_active']);
        });

        $now = now();
        DB::table('products')->orderBy('id')->chunkById(500, function ($products) use ($now): void {
            DB::table('product_variants')->insert(
                $products->map(fn ($product): array => [
                    'product_id' => $product->id,
                    'name' => 'پیش‌فرض',
                    'sku' => null,
                    'options' => null,
                    'stock_quantity' => 0,
                    'track_inventory' => true,
                    'is_active' => true,
                    'is_default' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
