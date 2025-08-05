<?php

// app/Http/Controllers/API/DashboardController.php
namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Models\Sale;
use App\Models\CurrentStock;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Get dashboard summary
     */
    public function summary(): JsonResponse
    {
        $result = $this->reportService->getDashboardSummary();
        return response()->json($result);
    }

    /**
     * Get sales chart data
     */
    public function salesChart(Request $request): JsonResponse
    {
        try {
            $days = $request->get('days', 7);
            $startDate = Carbon::now()->subDays($days)->startOfDay();

            $salesData = Sale::selectRaw('
                DATE(created_at) as date,
                COUNT(*) as transactions,
                SUM(total_amount) as total_sales,
                SUM(total_items) as total_items
            ')
            ->where('created_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            return response()->json([
                'success' => true,
                'data' => $salesData,
                'message' => "Sales chart data for last {$days} days"
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get sales chart: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get top products
     */
    public function topProducts(Request $request): JsonResponse
    {
        $days = $request->get('days', 30);
        $limit = $request->get('limit', 10);

        $result = $this->reportService->getBestSellingProducts(
            Carbon::now()->subDays($days)->toDateString(),
            Carbon::now()->toDateString(),
            $limit
        );

        return response()->json($result);
    }

    /**
     * Get recent activities
     */
    public function recentActivities(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 20);

            $activities = collect();

            // Recent sales
            $recentSales = Sale::with('user')
                              ->orderBy('created_at', 'desc')
                              ->limit($limit / 2)
                              ->get()
                              ->map(function ($sale) {
                                  return [
                                      'type' => 'sale',
                                      'title' => 'Sale ' . $sale->invoice_number,
                                      'description' => 'Rp ' . number_format($sale->total_amount, 0, ',', '.') . ' by ' . $sale->user->name,
                                      'timestamp' => $sale->created_at,
                                      'icon' => 'shopping-cart',
                                      'color' => 'green'
                                  ];
                              });

            // Recent stock movements
            $recentStockMovements = \App\Models\StockMovement::with(['productVariant.product', 'user'])
                                                           ->orderBy('created_at', 'desc')
                                                           ->limit($limit / 2)
                                                           ->get()
                                                           ->map(function ($movement) {
                                                               return [
                                                                   'type' => 'stock_movement',
                                                                   'title' => 'Stock ' . ucfirst($movement->type),
                                                                   'description' => $movement->productVariant->product->name . ' (' . $movement->quantity . ')',
                                                                   'timestamp' => $movement->created_at,
                                                                   'icon' => $movement->type === 'in' ? 'arrow-up' : 'arrow-down',
                                                                   'color' => $movement->type === 'in' ? 'blue' : 'orange'
                                                               ];
                                                           });

            $activities = $activities->concat($recentSales)
                                   ->concat($recentStockMovements)
                                   ->sortByDesc('timestamp')
                                   ->take($limit)
                                   ->values();

            return response()->json([
                'success' => true,
                'data' => $activities,
                'message' => 'Recent activities retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent activities: ' . $e->getMessage()
            ], 500);
        }
    }
}
