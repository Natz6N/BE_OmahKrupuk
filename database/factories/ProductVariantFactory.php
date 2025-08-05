<?php
// database/factories/ProductVariantFactory.php
namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductVariantFactory extends Factory
{
    protected $model = ProductVariant::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'variant_name' => fake()->randomElement([
                'Kemasan 100gr', 'Kemasan 250gr', 'Kemasan 500gr',
                'Kemasan Kecil', 'Kemasan Sedang', 'Kemasan Besar',
                'Sachet 10gr', 'Botol 500ml', 'Kotak 200gr'
            ]),
            'barcode' => fake()->unique()->ean13(),
            'barcode_type' => fake()->randomElement(['EAN', 'Code128']),
            'selling_price' => fake()->numberBetween(1000, 100000),
            'unit' => fake()->randomElement(['pcs', 'kg', 'liter', 'pack']),
            'is_active' => true,
        ];
    }
}
