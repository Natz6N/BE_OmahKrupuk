<?php
namespace Database\Seeders;

use App\Models\CurrentStock;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;

class CurrentStockSeeder extends Seeder
{
    public function run()
    {
        // Buat current stock untuk semua product variants
        $variants = ProductVariant::all();

        foreach ($variants as $variant) {
            CurrentStock::create([
                'product_variant_id' => $variant->id,
                'quantity' => rand(10, 100), // Random stok awal
                'min_stock' => rand(5, 15), // Random minimum stok
                'avg_purchase_price' => $variant->selling_price * 0.7, // Asumsi margin 30%
            ]);
        }
    }
}
