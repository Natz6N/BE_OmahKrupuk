<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all products with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'category_id', 'search', 'has_expiry', 'per_page', 'status'
        ]);

        try {
            $products = $this->productService->getAllProducts($filters);

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new product
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'has_expiry' => 'boolean',
            'variants' => 'sometimes|array',
            'variants.*.variant_name' => 'required_with:variants|string|max:255',
            'variants.*.barcode' => 'required_with:variants|string|unique:product_variants,barcode',
            'variants.*.selling_price' => 'required_with:variants|numeric|min:0',
            'variants.*.unit' => 'required_with:variants|string|max:50',
            'variants.*.min_stock' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->productService->createProduct($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get specific product
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load(['category', 'variants.currentStock']);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'brand' => 'nullable|string|max:255',
            'has_expiry' => 'sometimes|boolean',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->productService->updateProduct($product->id, $request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Delete product
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            // Check if product has variants with stock
            $hasStock = $product->variants()
                               ->whereHas('currentStock', function ($query) {
                                   $query->where('quantity', '>', 0);
                               })
                               ->exists();

            if ($hasStock) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete product with existing stock'
                ], 400);
            }

            $product->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Product deactivated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle product status
     */
    public function toggleStatus(Product $product): JsonResponse
    {
        try {
            $product->update(['is_active' => !$product->is_active]);

            return response()->json([
                'success' => true,
                'data' => $product,
                'message' => 'Product status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get product variants
     */
    public function variants(Product $product): JsonResponse
    {
        try {
            $variants = $product->variants()
                              ->with(['currentStock'])
                              ->where('is_active', true)
                              ->get();

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Product variants retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve variants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search products
     */
    public function search(Request $request, string $query): JsonResponse
    {
        try {
            $products = Product::with(['category', 'activeVariants.currentStock'])
                             ->where('is_active', true)
                             ->search($query)
                             ->limit(20)
                             ->get();

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Search completed successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick search for POS (simplified response)
     */
    public function quickSearch(Request $request, string $query): JsonResponse
    {
        try {
            $products = Product::with(['activeVariants' => function ($q) {
                                $q->with('currentStock')->where('is_active', true);
                            }])
                             ->where('is_active', true)
                             ->search($query)
                             ->limit(10)
                             ->get()
                             ->map(function ($product) {
                                 return [
                                     'id' => $product->id,
                                     'name' => $product->name,
                                     'brand' => $product->brand,
                                     'variants' => $product->activeVariants->map(function ($variant) {
                                         return [
                                             'id' => $variant->id,
                                             'name' => $variant->variant_name,
                                             'barcode' => $variant->barcode,
                                             'price' => $variant->selling_price,
                                             'unit' => $variant->unit,
                                             'stock' => $variant->current_quantity,
                                             'full_name' => $variant->full_name
                                         ];
                                     })
                                 ];
                             });

            return response()->json([
                'success' => true,
                'data' => $products,
                'message' => 'Quick search completed'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Quick search failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get popular products
     */
    public function popular(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);

            $popularProducts = \DB::table('sale_items')
                ->join('product_variants', 'sale_items.product_variant_id', '=', 'product_variants.id')
                ->join('products', 'product_variants.product_id', '=', 'products.id')
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->where('sales.created_at', '>=', now()->subDays($days))
                ->select(
                    'products.id',
                    'products.name',
                    'products.brand',
                    \DB::raw('SUM(sale_items.quantity) as total_sold'),
                    \DB::raw('COUNT(DISTINCT sales.id) as transaction_count')
                )
                ->groupBy('products.id', 'products.name', 'products.brand')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $popularProducts,
                'message' => "Popular products for last {$days} days"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get popular products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Duplicate product
     */
    public function duplicate(Product $product): JsonResponse
    {
        try {
            $newProduct = $product->replicate();
            $newProduct->name = $product->name . ' (Copy)';
            $newProduct->save();

            // Copy variants but generate new barcodes
            foreach ($product->variants as $variant) {
                $newVariant = $variant->replicate();
                $newVariant->product_id = $newProduct->id;
                $newVariant->barcode = $this->generateUniqueBarcode();
                $newVariant->save();

                // Create empty stock record
                \App\Models\CurrentStock::create([
                    'product_variant_id' => $newVariant->id,
                    'quantity' => 0,
                    'min_stock' => $variant->currentStock->min_stock ?? 5,
                    'avg_purchase_price' => 0
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $newProduct->load(['category', 'variants']),
                'message' => 'Product duplicated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to duplicate product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique barcode
     */
    private function generateUniqueBarcode(): string
    {
        do {
            $barcode = '2' . str_pad(rand(0, 999999999999), 12, '0', STR_PAD_LEFT);
        } while (\App\Models\ProductVariant::where('barcode', $barcode)->exists());

        return $barcode;
    }
}
