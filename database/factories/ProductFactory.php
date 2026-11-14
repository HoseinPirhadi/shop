<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'name' => $name,
            'slug' => fake()->unique()->slug(),
            'description' => fake()->optional()->paragraph(),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_active' => false,
        ];
    }
}
