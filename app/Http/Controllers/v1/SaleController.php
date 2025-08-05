<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class SaleController extends Controller
{
    protected $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Get all sales with filters
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'start_date', 'end_date', 'user_id', 'invoice_number', 'per_page'
        ]);

        try {
            $sales = $this->saleService->getSales($filters);

            return response()->json([
                'success' => true,
                'data' => $sales,
                'message' => 'Sales retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new sale
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_variant_id' => 'required|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'sometimes|numeric|min:0',
            'payment_method' => 'required|in:cash,card,transfer',
            'payment_amount' => 'required|numeric|min:0',
            'notes' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->saleService->createSale($request->all());

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get specific sale
     */
    public function show(Sale $sale): JsonResponse
    {
        $result = $this->saleService->getSaleById($sale->id);
        return response()->json($result, $result['success'] ? 200 : 404);
    }

    /**
     * Cancel sale
     */
    public function cancel(Sale $sale): JsonResponse
    {
        $result = $this->saleService->cancelSale($sale->id);
        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Find sale by invoice number
     */
    public function findByInvoice(string $invoice): JsonResponse
    {
        try {
            $sale = Sale::with(['items.productVariant.product', 'user'])
                       ->where('invoice_number', $invoice)
                       ->first();

            if (!$sale) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sale not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $sale,
                'message' => 'Sale found'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to find sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sale receipt data
     */
    public function receipt(Sale $sale): JsonResponse
    {
        try {
            $receipt = [
                'sale' => $sale->load(['items.productVariant.product', 'user']),
                'company' => [
                    'name' => config('app.name', 'Omah Krupuk'),
                    'address' => 'Alamat Toko',
                    'phone' => 'No. Telepon',
                    'email' => 'email@toko.com'
                ],
                'printed_at' => now(),
                'printed_by' => auth()->user()->name
            ];

            return response()->json([
                'success' => true,
                'data' => $receipt,
                'message' => 'Receipt data retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get receipt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent sales
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            $limit = $request->get('limit', 10);

            $sales = Sale::with(['items.productVariant.product', 'user'])
                        ->orderBy('created_at', 'desc')
                        ->limit($limit)
                        ->get()
                        ->map(function ($sale) {
                            return [
                                'id' => $sale->id,
                                'invoice_number' => $sale->invoice_number,
                                'total_amount' => $sale->total_amount,
                                'total_items' => $sale->total_items,
                                'cashier' => $sale->user->name,
                                'created_at' => $sale->created_at,
                                'items_preview' => $sale->items->take(3)->map(function ($item) {
                                    return $item->productVariant->product->name . ' x' . $item->quantity;
                                })->implode(', ')
                            ];
                        });

            return response()->json([
                'success' => true,
                'data' => $sales,
                'message' => 'Recent sales retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get recent sales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Quick sale for single item
     */
    public function quickSale(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'payment_amount' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $saleData = [
            'items' => [
                [
                    'product_variant_id' => $request->product_variant_id,
                    'quantity' => $request->quantity
                ]
            ],
            'payment_method' => 'cash',
            'payment_amount' => $request->payment_amount,
            'notes' => 'Quick sale'
        ];

        $result = $this->saleService->createSale($saleData);

        return response()->json($result, $result['success'] ? 201 : 400);
    }

    /**
     * Get daily summary (Admin only)
     */
    public function dailySummary(Request $request, string $date = null): JsonResponse
    {
        try {
            $targetDate = $date ? Carbon::parse($date) : Carbon::today();

            $summary = Sale::whereDate('created_at', $targetDate)
                          ->selectRaw('
                              COUNT(*) as total_transactions,
                              SUM(total_amount) as total_sales,
                              SUM(total_items) as total_items,
                              AVG(total_amount) as avg_transaction,
                              MIN(total_amount) as min_transaction,
                              MAX(total_amount) as max_transaction
                          ')
                          ->first();

            $hourlyData = Sale::whereDate('created_at', $targetDate)
                             ->selectRaw('
                                 HOUR(created_at) as hour,
                                 COUNT(*) as transactions,
                                 SUM(total_amount) as sales
                             ')
                             ->groupBy('hour')
                             ->orderBy('hour')
                             ->get();

            $topCashiers = Sale::whereDate('created_at', $targetDate)
                              ->join('users', 'sales.user_id', '=', 'users.id')
                              ->selectRaw('
                                  users.name,
                                  COUNT(*) as transactions,
                                  SUM(total_amount) as sales
                              ')
                              ->groupBy('users.id', 'users.name')
                              ->orderBy('sales', 'desc')
                              ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'date' => $targetDate->format('Y-m-d'),
                    'summary' => $summary,
                    'hourly_data' => $hourlyData,
                    'top_cashiers' => $topCashiers
                ],
                'message' => 'Daily summary retrieved'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get daily summary: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update sale (limited fields)
     */
    public function update(Request $request, Sale $sale): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => 'sometimes|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $sale->update($request->only('notes'));

            return response()->json([
                'success' => true,
                'data' => $sale,
                'message' => 'Sale updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update sale: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete sale (Admin only)
     */
    public function destroy(Sale $sale): JsonResponse
    {
        try {
            // Check if sale is recent (within 24 hours)
            if ($sale->created_at->diffInHours(now()) > 24) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete sales older than 24 hours'
                ], 400);
            }

            $result = $this->saleService->cancelSale($sale->id);

            if ($result['success']) {
                $sale->delete();
            }

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale: ' . $e->getMessage()
            ], 500);
        }
    }
}
