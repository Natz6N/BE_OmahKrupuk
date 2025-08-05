<?php
// database/factories/StockMovementFactory.php
namespace Database\Factories;

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['in', 'out', 'adjustment']);
        $quantity = $type === 'out' ? -fake()->numberBetween(1, 50) : fake()->numberBetween(1, 100);

        return [
            'product_variant_id' => ProductVariant::factory(),
            'supplier_id' => $type === 'in' ? Supplier::factory() : null,
            'user_id' => User::factory(),
            'type' => $type,
            'quantity' => $quantity,
            'purchase_price' => $type === 'in' ? fake()->numberBetween(500, 50000) : null,
            'batch_number' => $type === 'in' ? fake()->optional()->regexify('[A-Z]{3}[0-9]{6}') : null,
            'expired_date' => $type === 'in' && fake()->boolean(60) ? fake()->dateTimeBetween('+1 month', '+2 years') : null,
            'notes' => fake()->optional()->sentence(),
            'reference_type' => fake()->optional()->randomElement(['sale', 'purchase', 'adjustment']),
        ];
    }

    public function stockIn()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'in',
            'quantity' => fake()->numberBetween(10, 200),
            'supplier_id' => Supplier::factory(),
            'purchase_price' => fake()->numberBetween(1000, 80000),
            'batch_number' => fake()->regexify('[A-Z]{3}[0-9]{6}'),
        ]);
    }

    public function stockOut()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'out',
            'quantity' => -fake()->numberBetween(1, 20),
            'supplier_id' => null,
            'purchase_price' => null,
            'batch_number' => null,
        ]);
    }
}
