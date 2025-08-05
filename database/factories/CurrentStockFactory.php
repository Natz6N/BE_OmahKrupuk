<?php
namespace Database\Factories;

use App\Models\CurrentStock;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrentStockFactory extends Factory
{
    protected $model = CurrentStock::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(0, 200);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'quantity' => $quantity,
            'min_stock' => fake()->numberBetween(5, 20),
            'avg_purchase_price' => fake()->numberBetween(500, 50000),
        ];
    }

    public function lowStock()
    {
        return $this->state(function (array $attributes) {
            $minStock = fake()->numberBetween(10, 20);
            return [
                'quantity' => fake()->numberBetween(1, $minStock),
                'min_stock' => $minStock,
            ];
        });
    }

    public function outOfStock()
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 0,
        ]);
    }
}
