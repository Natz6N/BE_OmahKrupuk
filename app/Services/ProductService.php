<?php
// app/Services/ProductService.php
namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function getAllProducts($filters = [])
    {
        $query = Product::with(['category', 'activeVariants.currentStock'])
                       ->where('is_active', true);

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        if (isset($filters['has_expiry'])) {
            $query->where('has_expiry', $filters['has_expiry']);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function createProduct(array $data)
    {
        DB::beginTransaction();
        try {
            $product = Product::create([
                'category_id' => $data['category_id'],
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'brand' => $data['brand'] ?? null,
                'has_expiry' => $data['has_expiry'] ?? false,
                'is_active' => true
            ]);

            // Jika ada variants dalam request, buat sekaligus
            if (isset($data['variants']) && is_array($data['variants'])) {
                foreach ($data['variants'] as $variantData) {
                    $this->createProductVariant($product->id, $variantData);
                }
            }

            DB::commit();

            Log::info('Product created', [
                'product_id' => $product->id,
                'name' => $product->name,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => $product->load(['category', 'variants']),
                'message' => 'Produk berhasil dibuat'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to create product', [
                'error' => $e->getMessage(),
                'data' => $data,
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal membuat produk: ' . $e->getMessage()
            ];
        }
    }

    public function updateProduct($id, array $data)
    {
        DB::beginTransaction();
        try {
            $product = Product::findOrFail($id);

            $product->update([
                'category_id' => $data['category_id'] ?? $product->category_id,
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'brand' => $data['brand'] ?? $product->brand,
                'has_expiry' => $data['has_expiry'] ?? $product->has_expiry,
                'is_active' => $data['is_active'] ?? $product->is_active
            ]);

            DB::commit();

            Log::info('Product updated', [
                'product_id' => $product->id,
                'changes' => $product->getChanges(),
                'user_id' => auth()->id()
            ]);

            return [
                'success' => true,
                'data' => $product->load(['category', 'variants']),
                'message' => 'Produk berhasil diupdate'
            ];
        } catch (\Exception $e) {
            DB::rollback();

            Log::error('Failed to update product', [
                'product_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);

            return [
                'success' => false,
                'message' => 'Gagal mengupdate produk: ' . $e->getMessage()
            ];
        }
    }

    public function createProductVariant($productId, array $data)
    {
        DB::beginTransaction();
        try {
            // Validasi barcode unique
            if (ProductVariant::where('barcode', $data['barcode'])->exists()) {
                throw new \Exception('Barcode sudah digunakan');
            }

            $variant = ProductVariant::create([
                'product_id' => $productId,
                'variant_name' => $data['variant_name'],
                'barcode' => $data['barcode'],
                'barcode_type' => $data['barcode_type'] ?? 'EAN',
                'selling_price' => $data['selling_price'],
                'unit' => $data['unit'] ?? 'pcs',
                'is_active' => true
            ]);

            // Buat current stock record
            CurrentStock::create([
                'product_variant_id' => $variant->id,
                'quantity' => 0,
                'min_stock' => $data['min_stock'] ?? 5,
                'avg_purchase_price' => 0
            ]);

            DB::commit();

            Log::info('Product variant created', [
                'variant_id' => $variant->id,
                'product_id' => $productId,
                'barcode' => $variant->barcode,
                'user_id' => auth()->id()
            ]);

            return $variant->load(['product', 'currentStock']);
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
    }

    public function findByBarcode($barcode)
    {
        $variant = ProductVariant::with(['product.category', 'currentStock'])
                                ->where('barcode', $barcode)
                                ->where('is_active', true)
                                ->first();

        if (!$variant) {
            return [
                'success' => false,
                'message' => 'Produk dengan barcode tersebut tidak ditemukan'
            ];
        }

        return [
            'success' => true,
            'data' => $variant
        ];
    }

    public function getLowStockProducts()
    {
        $variants = ProductVariant::with(['product', 'currentStock'])
                                 ->whereHas('currentStock', function ($query) {
                                     $query->whereRaw('quantity <= min_stock');
                                 })
                                 ->where('is_active', true)
                                 ->get();

        return [
            'success' => true,
            'data' => $variants
        ];
    }
}
