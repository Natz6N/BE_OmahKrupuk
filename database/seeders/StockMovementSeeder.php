<?php

namespace Database\Seeders;

use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class StockMovementSeeder extends Seeder
{
    public function run()
    {
        $variants = ProductVariant::all();
        $suppliers = Supplier::all();
        $admin = User::where('role', 'admin')->first();

        // Buat beberapa stock movements sebagai sample data
        foreach ($variants as $variant) {
            // Stock masuk awal (3 bulan lalu)
            StockMovement::create([
                'product_variant_id' => $variant->id,
                'supplier_id' => $suppliers->random()->id,
                'user_id' => $admin->id,
                'type' => 'in',
                'quantity' => rand(50, 200),
                'purchase_price' => $variant->selling_price * 0.7,
                'batch_number' => 'BATCH-' . date('Ymd') . '-' . str_pad($variant->id, 3, '0', STR_PAD_LEFT),
                'expired_date' => $variant->product->has_expiry ? Carbon::now()->addMonths(rand(6, 24)) : null,
                'notes' => 'Stok awal masuk',
                'reference_type' => 'purchase',
                'created_at' => Carbon::now()->subMonths(3),
                'updated_at' => Carbon::now()->subMonths(3),
            ]);

            // Stock masuk tambahan (1 bulan lalu)
            if (rand(0, 1)) {
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'supplier_id' => $suppliers->random()->id,
                    'user_id' => $admin->id,
                    'type' => 'in',
                    'quantity' => rand(20, 100),
                    'purchase_price' => $variant->selling_price * 0.72,
                    'batch_number' => 'BATCH-' . date('Ymd', strtotime('-1 month')) . '-' . str_pad($variant->id, 3, '0', STR_PAD_LEFT),
                    'expired_date' => $variant->product->has_expiry ? Carbon::now()->addMonths(rand(6, 18)) : null,
                    'notes' => 'Restok barang',
                    'reference_type' => 'purchase',
                    'created_at' => Carbon::now()->subMonth(),
                    'updated_at' => Carbon::now()->subMonth(),
                ]);
            }

            // Beberapa adjustment
            if (rand(0, 2) == 0) {
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'supplier_id' => null,
                    'user_id' => $admin->id,
                    'type' => 'adjustment',
                    'quantity' => rand(-5, 5),
                    'purchase_price' => null,
                    'batch_number' => null,
                    'expired_date' => null,
                    'notes' => 'Penyesuaian stok fisik',
                    'reference_type' => 'adjustment',
                    'created_at' => Carbon::now()->subWeeks(rand(1, 8)),
                    'updated_at' => Carbon::now()->subWeeks(rand(1, 8)),
                ]);
            }

            // Produk expired/rusak (jarang terjadi)
            if (rand(0, 9) == 0 && $variant->product->has_expiry) {
                StockMovement::create([
                    'product_variant_id' => $variant->id,
                    'supplier_id' => null,
                    'user_id' => $admin->id,
                    'type' => 'expired',
                    'quantity' => -rand(1, 3),
                    'purchase_price' => null,
                    'batch_number' => null,
                    'expired_date' => Carbon::now()->subDays(rand(1, 30)),
                    'notes' => 'Produk expired dibuang',
                    'reference_type' => 'waste',
                    'created_at' => Carbon::now()->subDays(rand(1, 30)),
                    'updated_at' => Carbon::now()->subDays(rand(1, 30)),
                ]);
            }
        }
    }
}
