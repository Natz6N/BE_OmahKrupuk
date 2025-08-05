<?php
// app/Http/Controllers/API/ProductVariantController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ProductVariantController extends Controller
{
    protected $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Get all product variants
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = ProductVariant::with(['product.category', 'currentStock']);

            if ($request->has('product_id')) {
                $query->where('product_id', $request->product_id);
            }

            if ($request->has('status')) {
                $isActive = $request->status === 'active';
                $query->where('is_active', $isActive);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('variant_name', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%")
                      ->orWhereHas('product', function ($pq) use ($search) {
                          $pq->where('name', 'like', "%{$search}%");
                      });
                });
            }

            $variants = $query->orderBy('created_at', 'desc')
                            ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Product variants retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product variants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new product variant
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'variant_name' => 'required|string|max:255',
            'barcode' => 'required|string|unique:product_variants,barcode',
            'barcode_type' => 'sometimes|in:EAN,CODE128,CODE39',
            'selling_price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'min_stock' => 'sometimes|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $variant = $this->productService->createProductVariant(
                $request->product_id,
                $request->all()
            );

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Product variant created successfully'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create product variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific product variant
     */
    public function show(ProductVariant $variant): JsonResponse
    {
        try {
            $variant->load(['product.category', 'currentStock']);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Product variant retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve product variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update product variant
     */
    public function update(Request $request, ProductVariant $variant): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'variant_name' => 'sometimes|string|max:255',
            'barcode' => 'sometimes|string|unique:product_variants,barcode,' . $variant->id,
            'barcode_type' => 'sometimes|in:EAN,CODE128,CODE39',
            'selling_price' => 'sometimes|numeric|min:0',
            'unit' => 'sometimes|string|max:50',
            'is_active' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $variant->update($request->all());

            return response()->json([
                'success' => true,
                'data' => $variant->load(['product.category', 'currentStock']),
                'message' => 'Product variant updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update product variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete product variant
     */
    public function destroy(ProductVariant $variant): JsonResponse
    {
        try {
            // Check if variant has stock
            if ($variant->currentStock && $variant->currentStock->quantity > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete variant with existing stock'
                ], 400);
            }

            // Check if variant has sales history
            if ($variant->saleItems()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete variant with sales history'
                ], 400);
            }

            $variant->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product variant deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete product variant: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle variant status
     */
    public function toggleStatus(ProductVariant $variant): JsonResponse
    {
        try {
            $variant->update(['is_active' => !$variant->is_active]);

            return response()->json([
                'success' => true,
                'data' => $variant,
                'message' => 'Product variant status updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update variant status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get variant stock movements
     */
    public function stockMovements(ProductVariant $variant, Request $request): JsonResponse
    {
        try {
            $movements = $variant->stockMovements()
                               ->with(['supplier', 'user'])
                               ->orderBy('created_at', 'desc')
                               ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $movements,
                'message' => 'Variant stock movements retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock variants
     */
    public function lowStock(Request $request): JsonResponse
    {
        try {
            $variants = ProductVariant::with(['product', 'currentStock'])
                                    ->whereHas('currentStock', function ($query) {
                                        $query->whereRaw('quantity <= min_stock AND quantity > 0');
                                    })
                                    ->where('is_active', true)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Low stock variants retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve low stock variants: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get out of stock variants
     */
    public function outOfStock(Request $request): JsonResponse
    {
        try {
            $variants = ProductVariant::with(['product', 'currentStock'])
                                    ->whereHas('currentStock', function ($query) {
                                        $query->where('quantity', '<=', 0);
                                    })
                                    ->where('is_active', true)
                                    ->orderBy('created_at', 'desc')
                                    ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $variants,
                'message' => 'Out of stock variants retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve out of stock variants: ' . $e->getMessage()
            ], 500);
        }
    }
}

