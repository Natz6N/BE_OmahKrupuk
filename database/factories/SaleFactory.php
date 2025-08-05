<?php
namespace Database\Factories;

use App\Models\Sale;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    protected $model = Sale::class;

    public function definition(): array
    {
        $totalAmount = fake()->numberBetween(10000, 500000);
        $paymentAmount = $totalAmount + fake()->numberBetween(0, 50000);

        return [
            'user_id' => User::factory()->kasir(),
            'invoice_number' => 'INV-' . fake()->date('Ymd') . '-' . fake()->randomNumber(3, true),
            'total_amount' => $totalAmount,
            'total_items' => fake()->numberBetween(1, 10),
            'payment_method' => 'cash',
            'payment_amount' => $paymentAmount,
            'change_amount' => $paymentAmount - $totalAmount,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
