<?php
// app/Services/StockService.php
namespace App\Services;

use App\Models\StockMovement;
use App\Models\CurrentStock;
use App\Models\ProductVariant;
use App\Events\StockUpdated;
use App\Events\LowStockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockService
{
    public function stockIn(array $data)
    {
        DB::beginTransaction();
        try {
            $variant = ProductVariant::with('currentStock')->findOrFail($data['product_variant_id']);

            // Buat stock movement record
            $movement = StockMovement::create([
                'product_variant_id' => $data['product_variant_id'],
                'supplier_id' => $data['supplier_id'],
                'user_id' => auth()->id(),
                'type' => 'in',
                'quantity' => $data['quantity'],
                'purchase_price' => $data['purchase_price'],
                'batch_number' => $data['batch_number'] ?? null,
                'expired_date' => $data['expired_date'] ?? null,
                'notes' => $data['notes'] ?? 'Stok masuk',
                'reference_type' => 'purchase'
            ]);

            // Update current stock
            $this->updateCurrentStock($data['product_variant_id'], $data['quantity'], $data['purchase_price']);

            DB::commit();

            // Trigger event
            $currentStock = CurrentStock::where('product_variant_id', $data['product_variant_id'])->first();
            event(new StockUpdated($data['product_variant_id'], $currentStock->quantity, 'in'));

            Log::info('Stock in successful', [
                'movement_id' => $movement->id,
                'product_variant_id' => $data['product_variant_id'],
                'quantity' => $data['quantity'],
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => $movement->load(['productVariant.product', 'supplier', 'user']),
                'message' => 'Stok berhasil ditambahkan'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Stock in failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menambah stok: ' . $e->getMessage()
            ];
        }
    }

    public function stockOut($productVariantId, $quantity, $notes = null, $referenceType = null, $referenceId = null)
    {
        DB::beginTransaction();
        try {
            $currentStock = CurrentStock::where('product_variant_id', $productVariantId)->first();

            if (!$currentStock || $currentStock->quantity < $quantity) {
                throw new \Exception('Stok tidak mencukupi');
            }

            // Buat stock movement record
            $movement = StockMovement::create([
                'product_variant_id' => $productVariantId,
                'supplier_id' => null,
                'user_id' => auth()->id(),
                'type' => 'out',
                'quantity' => -$quantity, // Negatif untuk keluar
                'purchase_price' => null,
                'batch_number' => null,
                'expired_date' => null,
                'notes' => $notes ?? 'Stok keluar',
                'reference_type' => $referenceType,
                'reference_id' => $referenceId
            ]);

            // Update current stock
            $this->updateCurrentStock($productVariantId, -$quantity);

            // Check jika stok menipis
            $updatedStock = CurrentStock::where('product_variant_id', $productVariantId)->first();
            if ($updatedStock->quantity <= $updatedStock->min_stock) {
                $variant = ProductVariant::with('product')->find($productVariantId);
                event(new LowStockAlert($variant, $updatedStock->quantity));
            }

            DB::commit();

            // Trigger event
            event(new StockUpdated($productVariantId, $updatedStock->quantity, 'out'));

            return [
                'success' => true,
                'data' => $movement,
                'message' => 'Stok berhasil dikurangi'
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function adjustStock(array $data)
    {
        DB::beginTransaction();
        try {
            $currentStock = CurrentStock::where('product_variant_id', $data['product_variant_id'])->first();

            if (!$currentStock) {
                throw new \Exception('Data stok tidak ditemukan');
            }

            $adjustment = $data['new_quantity'] - $currentStock->quantity;

            // Buat stock movement record
            $movement = StockMovement::create([
                'product_variant_id' => $data['product_variant_id'],
                'supplier_id' => null,
                'user_id' => auth()->id(),
                'type' => 'adjustment',
                'quantity' => $adjustment,
                'purchase_price' => null,
                'batch_number' => null,
                'expired_date' => null,
                'notes' => $data['notes'] ?? 'Penyesuaian stok',
                'reference_type' => 'adjustment'
            ]);

            // Update current stock langsung ke quantity baru
            $currentStock->update([
                'quantity' => $data['new_quantity'],
                'min_stock' => $data['min_stock'] ?? $currentStock->min_stock
            ]);

            DB::commit();

            // Trigger event
            event(new StockUpdated($data['product_variant_id'], $data['new_quantity'], 'adjustment'));

            Log::info('Stock adjustment successful', [
                'movement_id' => $movement->id,
                'product_variant_id' => $data['product_variant_id'],
                'old_quantity' => $currentStock->quantity - $adjustment,
                'new_quantity' => $data['new_quantity'],
                'adjustment' => $adjustment,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => $movement->load(['productVariant.product', 'user']),
                'message' => 'Stok berhasil disesuaikan'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Stock adjustment failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal menyesuaikan stok: ' . $e->getMessage()
            ];
        }
    }

    public function getStockMovements($filters = [])
    {
        $query = StockMovement::with(['productVariant.product', 'supplier', 'user']);

        if (isset($filters['product_variant_id'])) {
            $query->where('product_variant_id', $filters['product_variant_id']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['supplier_id'])) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        return $query->orderBy('created_at', 'desc')
                     ->paginate($filters['per_page'] ?? 15);
    }

    public function getExpiringProducts($days = 30)
    {
        $movements = StockMovement::with(['productVariant.product'])
                                ->where('type', 'in')
                                ->where('expired_date', '<=', now()->addDays($days))
                                ->where('expired_date', '>', now())
                                ->orderBy('expired_date', 'asc')
                                ->get();

        return [
            'success' => true,
            'data' => $movements
        ];
    }

    private function updateCurrentStock($productVariantId, $quantityChange, $purchasePrice = null)
    {
        $currentStock = CurrentStock::where('product_variant_id', $productVariantId)->first();

        if (!$currentStock) {
            // Buat record baru jika belum ada
            CurrentStock::create([
                'product_variant_id' => $productVariantId,
                'quantity' => max(0, $quantityChange),
                'min_stock' => 5,
                'avg_purchase_price' => $purchasePrice ?? 0
            ]);
        } else {
            $newQuantity = $currentStock->quantity + $quantityChange;

            // Update rata-rata harga beli jika ada stok masuk dengan harga
            $newAvgPrice = $currentStock->avg_purchase_price;
            if ($quantityChange > 0 && $purchasePrice) {
                $totalValue = ($currentStock->quantity * $currentStock->avg_purchase_price) +
                             ($quantityChange * $purchasePrice);
                $newAvgPrice = $totalValue / $newQuantity;
            }

            $currentStock->update([
                'quantity' => max(0, $newQuantity),
                'avg_purchase_price' => $newAvgPrice
            ]);
        }
    }
}
