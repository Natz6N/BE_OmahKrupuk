<?php
// app/Services/ReportService.php
namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\ProductVariant;
use App\Models\CurrentStock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    public function getDashboardSummary()
    {
        $today = Carbon::today();
        $thisMonth = Carbon::now()->startOfMonth();

        return [
            'success' => true,
            'data' => [
                'today_sales' => [
                    'total_amount' => Sale::whereDate('created_at', $today)->sum('total_amount'),
                    'total_transactions' => Sale::whereDate('created_at', $today)->count(),
                    'total_items' => Sale::whereDate('created_at', $today)->sum('total_items')
                ],
                'month_sales' => [
                    'total_amount' => Sale::where('created_at', '>=', $thisMonth)->sum('total_amount'),
                    'total_transactions' => Sale::where('created_at', '>=', $thisMonth)->count(),
                    'total_items' => Sale::where('created_at', '>=', $thisMonth)->sum('total_items')
                ],
                'low_stock_count' => CurrentStock::whereRaw('quantity <= min_stock')->count(),
                'out_of_stock_count' => CurrentStock::where('quantity', '<=', 0)->count(),
                'total_products' => ProductVariant::where('is_active', true)->count(),
                'expiring_soon_count' => StockMovement::where('expired_date', '<=', Carbon::now()->addDays(30))
                                                     ->where('expired_date', '>', Carbon::now())
                                                     ->count()
            ]
        ];
    }

    public function getSalesReport($startDate, $endDate, $groupBy = 'daily')
    {
        $query = Sale::whereBetween('created_at', [$startDate, $endDate]);

        switch ($groupBy) {
            case 'daily':
                $results = $query->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_amount,
                    SUM(total_items) as total_items
                ')
                ->groupBy('date')
                ->orderBy('date')
                ->get();
                break;

            case 'monthly':
                $results = $query->selectRaw('
                    YEAR(created_at) as year,
                    MONTH(created_at) as month,
                    COUNT(*) as total_transactions,
                    SUM(total_amount) as total_amount,
                    SUM(total_items) as total_items
                ')
                ->groupBy('year', 'month')
                ->orderBy('year')
                ->orderBy('month')
                ->get();
                break;

            default:
                $results = $query->get();
        }

        return [
            'success' => true,
            'data' => $results
        ];
    }

    public function getBestSellingProducts($startDate = null, $endDate = null, $limit = 10)
    {
        $query = SaleItem::with(['productVariant.product'])
                         ->selectRaw('
                             product_variant_id,
                             SUM(quantity) as total_sold,
                             SUM(total_price) as total_revenue,
                             AVG(unit_price) as avg_price,
                             COUNT(DISTINCT sale_id) as transaction_count
                         ')
                         ->groupBy('product_variant_id');

        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [$startDate, $endDate]);
        }

        $results = $query->orderBy('total_sold', 'desc')
                        ->limit($limit)
                        ->get();

        return [
            'success' => true,
            'data' => $results
        ];
    }

    public function getProfitLossReport($startDate, $endDate)
    {
        $sales = Sale::with('items')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();

        $totalRevenue = 0;
        $totalCost = 0;
        $totalProfit = 0;

        foreach ($sales as $sale) {
            $totalRevenue += $sale->total_amount;

            foreach ($sale->items as $item) {
                $cost = $item->quantity * ($item->purchase_price ?? 0);
                $totalCost += $cost;
                $totalProfit += ($item->total_price - $cost);
            }
        }

        $profitMargin = $totalRevenue > 0 ? (($totalProfit / $totalRevenue) * 100) : 0;

        return [
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate
                ],
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'profit' => $totalProfit,
                'profit_margin' => round($profitMargin, 2),
                'total_transactions' => $sales->count()
            ]
        ];
    }

    public function getStockValuation()
    {
        $stocks = CurrentStock::with('productVariant.product')
                             ->where('quantity', '>', 0)
                             ->get();

        $totalValue = 0;
        $categoryValues = [];

        foreach ($stocks as $stock) {
            $value = $stock->quantity * $stock->avg_purchase_price;
            $totalValue += $value;

            $categoryName = $stock->productVariant->product->category->name ?? 'Tanpa Kategori';
            if (!isset($categoryValues[$categoryName])) {
                $categoryValues[$categoryName] = 0;
            }
            $categoryValues[$categoryName] += $value;
        }

        return [
            'success' => true,
            'data' => [
                'total_value' => $totalValue,
                'total_items' => $stocks->sum('quantity'),
                'total_variants' => $stocks->count(),
                'by_category' => $categoryValues
            ]
        ];
    }

    public function getCashierPerformance($startDate, $endDate)
    {
        $performance = Sale::with('user')
                          ->whereBetween('created_at', [$startDate, $endDate])
                          ->selectRaw('
                              user_id,
                              COUNT(*) as total_transactions,
                              SUM(total_amount) as total_sales,
                              SUM(total_items) as total_items,
                              AVG(total_amount) as avg_transaction_value
                          ')
                          ->groupBy('user_id')
                          ->orderBy('total_sales', 'desc')
                          ->get();

        return [
            'success' => true,
            'data' => $performance
        ];
    }
}
