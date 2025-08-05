<?php

// app/Http/Controllers/API/ReportController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Sales report
     */
    public function sales(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'sometimes|in:daily,monthly'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->reportService->getSalesReport(
            $request->start_date,
            $request->end_date,
            $request->get('group_by', 'daily')
        );

        return response()->json($result);
    }

    /**
     * Profit & Loss report
     */
    public function profitLoss(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->reportService->getProfitLossReport(
            $request->start_date,
            $request->end_date
        );

        return response()->json($result);
    }

    /**
     * Stock valuation report
     */
    public function stockValuation(): JsonResponse
    {
        $result = $this->reportService->getStockValuation();
        return response()->json($result);
    }

    /**
     * Best selling products report
     */
    public function bestSelling(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $limit = $request->get('limit', 10);

        $result = $this->reportService->getBestSellingProducts($startDate, $endDate, $limit);
        return response()->json($result);
    }

    /**
     * Cashier performance report
     */
    public function cashierPerformance(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->reportService->getCashierPerformance(
            $request->start_date,
            $request->end_date
        );

        return response()->json($result);
    }

    /**
     * Inventory movements report
     */
    public function inventoryMovements(Request $request): JsonResponse
    {
        try {
            $query = \App\Models\StockMovement::with(['productVariant.product', 'supplier', 'user']);

            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('created_at', [$request->start_date, $request->end_date]);
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            $movements = $query->orderBy('created_at', 'desc')
                             ->paginate($request->get('per_page', 15));

            return response()->json([
                'success' => true,
                'data' => $movements,
                'message' => 'Inventory movements retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get inventory movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Low stock history report
     */
    public function lowStockHistory(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);

            $lowStockItems = \App\Models\CurrentStock::with(['productVariant.product'])
                                                   ->whereRaw('quantity <= min_stock')
                                                   ->get()
                                                   ->map(function ($stock) use ($days) {
                                                       $recentMovements = $stock->productVariant
                                                                               ->stockMovements()
                                                                               ->where('created_at', '>=', Carbon::now()->subDays($days))
                                                                               ->orderBy('created_at', 'desc')
                                                                               ->get();

                                                       return [
                                                           'product' => $stock->productVariant->product->name,
                                                           'variant' => $stock->productVariant->variant_name,
                                                           'current_stock' => $stock->quantity,
                                                           'min_stock' => $stock->min_stock,
                                                           'recent_movements' => $recentMovements
                                                       ];
                                                   });

            return response()->json([
                'success' => true,
                'data' => $lowStockItems,
                'message' => "Low stock history for last {$days} days"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get low stock history: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Expiry report
     */
    public function expiryReport(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 30);

            $expiringProducts = \App\Models\StockMovement::with(['productVariant.product'])
                                                        ->where('type', 'in')
                                                        ->where('expired_date', '<=', Carbon::now()->addDays($days))
                                                        ->where('expired_date', '>', Carbon::now())
                                                        ->orderBy('expired_date', 'asc')
                                                        ->get();

            $expiredProducts = \App\Models\StockMovement::with(['productVariant.product'])
                                                       ->where('type', 'in')
                                                       ->where('expired_date', '<', Carbon::now())
                                                       ->whereNotNull('expired_date')
                                                       ->orderBy('expired_date', 'desc')
                                                       ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'expiring_soon' => $expiringProducts,
                    'expired' => $expiredProducts,
                    'summary' => [
                        'expiring_count' => $expiringProducts->count(),
                        'expired_count' => $expiredProducts->count(),
                        'total_expiring_value' => $expiringProducts->sum(function ($item) {
                            return $item->quantity * $item->purchase_price;
                        }),
                        'total_expired_value' => $expiredProducts->sum(function ($item) {
                            return $item->quantity * $item->purchase_price;
                        })
                    ]
                ],
                'message' => 'Expiry report generated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate expiry report: ' . $e->getMessage()
            ], 500);
        }
    }
}
