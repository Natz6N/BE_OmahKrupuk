<?php

namespace Database\Seeders;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\CurrentStock;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SampleSalesSeeder extends Seeder
{
    public function run()
    {
        $kasir = User::where('role', 'kasir')->first();
        $admin = User::where('role', 'admin')->first();
        $variants = ProductVariant::with('currentStock')->get();

        // Generate sales untuk 30 hari terakhir
        for ($i = 30; $i >= 1; $i--) {
            $saleDate = Carbon::now()->subDays($i);
            $salesCount = rand(5, 15); // 5-15 transaksi per hari

            for ($j = 0; $j < $salesCount; $j++) {
                $this->createSampleSale($kasir, $admin, $variants, $saleDate);
            }
        }
    }

    private function createSampleSale($kasir, $admin, $variants, $saleDate)
    {
        DB::beginTransaction();
        try {
            // Pilih user yang melakukan transaksi (80% kasir, 20% admin)
            $user = rand(1, 10) <= 8 ? $kasir : $admin;

            // Generate invoice number
            $invoiceNumber = 'INV-' . $saleDate->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);

            // Pastikan invoice number unique
            while (Sale::where('invoice_number', $invoiceNumber)->exists()) {
                $invoiceNumber = 'INV-' . $saleDate->format('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            }

            // Pilih 1-5 produk random untuk dijual
            $itemCount = rand(1, 5);
            $selectedVariants = $variants->random($itemCount);

            $totalAmount = 0;
            $totalItems = 0;
            $saleItems = [];

            foreach ($selectedVariants as $variant) {
                // Pastikan ada stok
                if (!$variant->currentStock || $variant->currentStock->quantity <= 0) {
                    continue;
                }

                $quantity = min(rand(1, 3), $variant->currentStock->quantity);
                $unitPrice = $variant->selling_price;
                $totalPrice = $quantity * $unitPrice;

                $totalAmount += $totalPrice;
                $totalItems += $quantity;

                $saleItems[] = [
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'total_price' => $totalPrice,
                    'purchase_price' => $variant->currentStock->avg_purchase_price,
                ];
            }

            // Skip jika tidak ada item yang bisa dijual
            if (empty($saleItems)) {
                DB::rollback();
                return;
            }

            // Buat sale record
            $paymentAmount = $totalAmount + rand(0, 50000); // Kadang pembeli bayar lebih
            $changeAmount = $paymentAmount - $totalAmount;

            $sale = Sale::create([
                'user_id' => $user->id,
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totalAmount,
                'total_items' => $totalItems,
                'payment_method' => 'cash',
                'payment_amount' => $paymentAmount,
                'change_amount' => $changeAmount,
                'notes' => $this->getRandomNotes(),
                'created_at' => $saleDate->addMinutes(rand(480, 1200)), // Jam 8 pagi - 8 malam
                'updated_at' => $saleDate,
            ]);

            // Buat sale items dan update stok
            foreach ($saleItems as $item) {
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_variant_id' => $item['product_variant_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price'],
                    'purchase_price' => $item['purchase_price'],
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->created_at,
                ]);

                // Buat stock movement untuk penjualan
                StockMovement::create([
                    'product_variant_id' => $item['product_variant_id'],
                    'supplier_id' => null,
                    'user_id' => $user->id,
                    'type' => 'out',
                    'quantity' => -$item['quantity'], // Negatif karena keluar
                    'purchase_price' => null,
                    'batch_number' => null,
                    'expired_date' => null,
                    'notes' => 'Penjualan - ' . $sale->invoice_number,
                    'reference_type' => 'sale',
                    'reference_id' => $sale->id,
                    'created_at' => $sale->created_at,
                    'updated_at' => $sale->created_at,
                ]);

                // Update current stock
                CurrentStock::where('product_variant_id', $item['product_variant_id'])
                    ->decrement('quantity', $item['quantity']);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    private function getRandomNotes()
    {
        $notes = [
            null,
            'Pembeli reguler',
            'Pembeli baru',
            'Beli untuk hajatan',
            'Pesanan khusus',
            'Beli grosir',
            'Customer tetap',
            '',
        ];

        return $notes[array_rand($notes)];
    }
}
