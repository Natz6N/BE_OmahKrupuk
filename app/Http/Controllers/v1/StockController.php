<?php

// app/Http/Controllers/API/StockController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\StockService;
use App\Models\StockMovement;
use App\Models\CurrentStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Get current stock levels
     */
    public function currentStock(Request $request): JsonResponse
    {
        try {
            $query = CurrentStock::with(['productVariant.product.category']);

            // Filters
            if ($request->has('category_id')) {
                $query->whereHas('productVariant.product', function ($q) use ($request) {
                    $q->where('category_id', $request->category_id);
                });
            }

            if ($request->has('status')) {
                switch ($request->status) {
                    case 'low_stock':
                        $query->whereRaw('quantity <= min_stock AND quantity > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('quantity', '<=', 0);
                        break;
                    case 'normal':
                        $query->whereRaw('quantity > min_stock');
                        break;
                }
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('productVariant.product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('brand', 'like', "%{$search}%");
                });
            }

            $stocks = $query->orderBy('quantity', 'asc')
                           ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $stocks,
                'message' => 'Current stock retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve current stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock movements
     */
    public function movements(Request $request): JsonResponse
    {
        $filters = $request->only([
            'product_variant_id', 'type', 'start_date', 'end_date',
            'supplier_id', 'per_page'
        ]);

        try {
            $movements = $this->stockService->getStockMovements($filters);

            return response()->json([
                'success' => true,
                'data' => $movements,
                'message' => 'Stock movements retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get specific stock movement
     */
    public function showMovement(StockMovement $movement): JsonResponse
    {
        try {
            $movement->load(['productVariant.product', 'supplier', 'user']);

            return response()->json([
                'success' => true,
                'data' => $movement,
                'message' => 'Stock movement retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock movement: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Stock in operation
     */
    public function stockIn(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'quantity' => 'required|integer|min:1',
            'purchase_price' => 'required|numeric|min:0',
            'batch_number' => 'nullable|string|max:100',
            'expired_date' => 'nullable|date|after:today',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->stockService->stockIn($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Stock adjustment
     */
    public function adjustment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'new_quantity' => 'required|integer|min:0',
            'min_stock' => 'sometimes|integer|min:0',
            'notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->stockService->adjustStock($request->all());

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Bulk stock adjustment
     */
    public function bulkAdjustment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'adjustments' => 'required|array|min:1',
            'adjustments.*.product_variant_id' => 'required|exists:product_variants,id',
            'adjustments.*.new_quantity' => 'required|integer|min:0',
            'adjustments.*.notes' => 'nullable|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $results = [];
            $successCount = 0;
            $failedCount = 0;

            foreach ($request->adjustments as $adjustment) {
                $result = $this->stockService->adjustStock($adjustment);
                $results[] = $result;

                if ($result['success']) {
                    $successCount++;
                } else {
                    $failedCount++;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'total_processed' => count($request->adjustments),
                    'successful' => $successCount,
                    'failed' => $failedCount,
                    'details' => $results
                ],
                'message' => "Bulk adjustment completed: {$successCount} successful, {$failedCount} failed"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Bulk adjustment failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get expiring products
     */
    public function expiringProducts(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);

        $result = $this->stockService->getExpiringProducts($days);

        return response()->json($result);
    }

    /**
     * Get expired products
     */
    public function expiredProducts(Request $request): JsonResponse
    {
        try {
            $expiredProducts = StockMovement::with(['productVariant.product'])
                                          ->where('type', 'in')
                                          ->where('expired_date', '<', now())
                                          ->whereNotNull('expired_date')
                                          ->orderBy('expired_date', 'desc')
                                          ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $expiredProducts,
                'message' => 'Expired products retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get expired products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get stock alerts summary
     */
    public function alerts(Request $request): JsonResponse
    {
        try {
            $lowStockCount = CurrentStock::whereRaw('quantity <= min_stock AND quantity > 0')->count();
            $outOfStockCount = CurrentStock::where('quantity', '<=', 0)->count();

            $expiringCount = StockMovement::where('type', 'in')
                                        ->where('expired_date', '<=', now()->addDays(30))
                                        ->where('expired_date', '>', now())
                                        ->count();

            $expiredCount = StockMovement::where('type', 'in')
                                       ->where('expired_date', '<', now())
                                       ->whereNotNull('expired_date')
                                       ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'low_stock' => $lowStockCount,
                    'out_of_stock' => $outOfStockCount,
                    'expiring_soon' => $expiringCount,
                    'expired' => $expiredCount,
                    'total_alerts' => $lowStockCount + $outOfStockCount + $expiringCount + $expiredCount
                ],
                'message' => 'Stock alerts retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get stock alerts: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete stock movement (Admin only)
     */
    public function deleteMovement(StockMovement $movement): JsonResponse
    {
        try {
            // Only allow deletion of recent movements (within 24 hours)
            if ($movement->created_at->diffInHours(now()) > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete stock movements older than 24 hours'
                ], 400);
            }

            // Don't allow deletion of sale-related movements
            if ($movement->reference_type === 'sale') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete sale-related stock movements'
                ], 400);
            }

            $movement->delete();

            return response()->json([
                'success' => true,
                'message' => 'Stock movement deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stock movement: ' . $e->getMessage()
            ], 500);
        }
    }
}
