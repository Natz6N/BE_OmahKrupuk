<?php
namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'brand' => fake()->company(),
            'has_expiry' => fake()->boolean(70), // 70% chance has expiry
            'is_active' => true,
        ];
    }

    public function withExpiry()
    {
        return $this->state(fn (array $attributes) => [
            'has_expiry' => true,
        ]);
    }

    public function withoutExpiry()
    {
        return $this->state(fn (array $attributes) => [
            'has_expiry' => false,
        ]);
    }
}
