<?php
namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use App\Events\SaleCompleted;
use App\Events\StockUpdated;
use App\Events\LowStockAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleService
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    public function createSale(array $data)
    {
        DB::beginTransaction();
        try {
            // Validasi stok untuk semua item
            $this->validateStock($data['items']);

            // Generate invoice number
            $invoiceNumber = Sale::generateInvoiceNumber();

            // Hitung total
            $totals = $this->calculateTotals($data['items']);

            // Buat sale record
            $sale = Sale::create([
                'user_id' => auth()->id(),
                'invoice_number' => $invoiceNumber,
                'total_amount' => $totals['total_amount'],
                'total_items' => $totals['total_items'],
                'payment_method' => $data['payment_method'] ?? 'cash',
                'payment_amount' => $data['payment_amount'],
                'change_amount' => $data['payment_amount'] - $totals['total_amount'],
                'notes' => $data['notes'] ?? null
            ]);

            // Buat sale items dan update stok
            $this->createSaleItems($sale, $data['items']);

            DB::commit();

            // Trigger events
            event(new SaleCompleted($sale->load(['items.productVariant.product', 'user'])));

            Log::info('Sale completed', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'total_amount' => $sale->total_amount,
                'total_items' => $sale->total_items,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => $sale->load(['items.productVariant.product', 'user']),
                'message' => 'Transaksi berhasil'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Sale creation failed', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Transaksi gagal: ' . $e->getMessage()
            ];
        }
    }

    public function getSales($filters = [])
    {
        $query = Sale::with(['items.productVariant.product', 'user']);

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['invoice_number'])) {
            $query->where('invoice_number', 'like', '%' . $filters['invoice_number'] . '%');
        }

        return $query->orderBy('created_at', 'desc')
                     ->paginate($filters['per_page'] ?? 15);
    }

    public function getSaleById($id)
    {
        $sale = Sale::with(['items.productVariant.product', 'user'])->find($id);

        if (!$sale) {
            return [
                'success' => false,
                'message' => 'Transaksi tidak ditemukan'
            ];
        }

        return [
            'success' => true,
            'data' => $sale
        ];
    }

    public function cancelSale($id)
    {
        DB::beginTransaction();
        try {
            $sale = Sale::with('items')->findOrFail($id);

            // Kembalikan stok untuk setiap item
            foreach ($sale->items as $item) {
                $this->stockService->stockOut(
                    $item->product_variant_id,
                    -$item->quantity, // Positif untuk mengembalikan stok
                    'Pembatalan penjualan - ' . $sale->invoice_number,
                    'cancellation',
                    $sale->id
                );
            }

            // Soft delete atau marking sebagai cancelled
            $sale->update(['notes' => ($sale->notes ?? '') . ' [DIBATALKAN]']);

            DB::commit();

            Log::info('Sale cancelled', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Sale cancellation failed', [
                'sale_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage()
            ];
        }
    }

    private function validateStock(array $items)
    {
        foreach ($items as $item) {
            $variant = ProductVariant::with('currentStock')->find($item['product_variant_id']);

            if (!$variant) {
                throw new \Exception("Produk dengan ID {$item['product_variant_id']} tidak ditemukan");
            }

            if (!$variant->is_active) {
                throw new \Exception("Produk {$variant->full_name} tidak aktif");
            }

            $currentStock = $variant->currentStock->quantity ?? 0;
            if ($currentStock < $item['quantity']) {
                throw new \Exception("Stok {$variant->full_name} tidak mencukupi. Tersedia: {$currentStock}, diminta: {$item['quantity']}");
            }
        }
    }

    private function calculateTotals(array $items)
    {
        $totalAmount = 0;
        $totalItems = 0;

        foreach ($items as $item) {
            $variant = ProductVariant::find($item['product_variant_id']);
            $unitPrice = $item['unit_price'] ?? $variant->selling_price;
            $totalAmount += $unitPrice * $item['quantity'];
            $totalItems += $item['quantity'];
        }

        return [
            'total_amount' => $totalAmount,
            'total_items' => $totalItems
        ];
    }

    private function createSaleItems(Sale $sale, array $items)
    {
        foreach ($items as $item) {
            $variant = ProductVariant::with('currentStock')->find($item['product_variant_id']);
            $unitPrice = $item['unit_price'] ?? $variant->selling_price;
            $totalPrice = $unitPrice * $item['quantity'];

            // Buat sale item
            SaleItem::create([
                'sale_id' => $sale->id,
                'product_variant_id' => $item['product_variant_id'],
                'quantity' => $item['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'purchase_price' => $variant->currentStock->avg_purchase_price ?? 0
            ]);

            // Update stok
            $this->stockService->stockOut(
                $item['product_variant_id'],
                $item['quantity'],
                'Penjualan - ' . $sale->invoice_number,
                'sale',
                $sale->id
            );
        }
    }
}
