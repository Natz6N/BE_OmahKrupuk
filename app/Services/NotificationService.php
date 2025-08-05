<?php
namespace App\Services;

use App\Models\CurrentStock;
use App\Models\StockMovement;
use App\Models\Sale;
use Carbon\Carbon;

class NotificationService
{
    public function getLowStockNotifications()
    {
        $lowStocks = CurrentStock::with('productVariant.product')
                                ->whereRaw('quantity <= min_stock')
                                ->where('quantity', '>', 0)
                                ->get();

        $notifications = [];
        foreach ($lowStocks as $stock) {
            $notifications[] = [
                'type' => 'low_stock',
                'title' => 'Stok Menipis',
                'message' => "{$stock->productVariant->full_name} tersisa {$stock->quantity} {$stock->productVariant->unit}",
                'data' => [
                    'product_variant_id' => $stock->product_variant_id,
                    'current_stock' => $stock->quantity,
                    'min_stock' => $stock->min_stock,
                    'product_name' => $stock->productVariant->product->name,
                    'variant_name' => $stock->productVariant->variant_name
                ],
                'priority' => 'medium',
                'created_at' => now()
            ];
        }

        return $notifications;
    }

    public function getOutOfStockNotifications()
    {
        $outOfStocks = CurrentStock::with('productVariant.product')
                                  ->where('quantity', '<=', 0)
                                  ->get();

        $notifications = [];
        foreach ($outOfStocks as $stock) {
            $notifications[] = [
                'type' => 'out_of_stock',
                'title' => 'Stok Habis',
                'message' => "{$stock->productVariant->full_name} sudah habis",
                'data' => [
                    'product_variant_id' => $stock->product_variant_id,
                    'product_name' => $stock->productVariant->product->name,
                    'variant_name' => $stock->productVariant->variant_name
                ],
                'priority' => 'high',
                'created_at' => now()
            ];
        }

        return $notifications;
    }

    public function getExpiringProductNotifications($days = 30)
    {
        $expiringProducts = StockMovement::with('productVariant.product')
                                        ->where('type', 'in')
                                        ->where('expired_date', '<=', Carbon::now()->addDays($days))
                                        ->where('expired_date', '>', Carbon::now())
                                        ->orderBy('expired_date', 'asc')
                                        ->get();

        $notifications = [];
        foreach ($expiringProducts as $movement) {
            $daysUntilExpiry = Carbon::now()->diffInDays($movement->expired_date);

            $notifications[] = [
                'type' => 'expiring_soon',
                'title' => 'Produk Akan Expired',
                'message' => "{$movement->productVariant->full_name} akan expired dalam {$daysUntilExpiry} hari",
                'data' => [
                    'product_variant_id' => $movement->product_variant_id,
                    'batch_number' => $movement->batch_number,
                    'expired_date' => $movement->expired_date,
                    'days_until_expiry' => $daysUntilExpiry,
                    'product_name' => $movement->productVariant->product->name,
                    'variant_name' => $movement->productVariant->variant_name
                ],
                'priority' => $daysUntilExpiry <= 7 ? 'high' : 'medium',
                'created_at' => now()
            ];
        }

        return $notifications;
    }

    public function getTodaySalesSummaryNotification()
    {
        $todaySales = Sale::whereDate('created_at', Carbon::today())
                         ->selectRaw('
                             COUNT(*) as total_transactions,
                             SUM(total_amount) as total_amount,
                             SUM(total_items) as total_items
                         ')
                         ->first();

        return [
            'type' => 'daily_summary',
            'title' => 'Ringkasan Penjualan Hari Ini',
            'message' => "Total {$todaySales->total_transactions} transaksi dengan nilai Rp " . number_format($todaySales->total_amount, 0, ',', '.'),
            'data' => [
                'total_transactions' => $todaySales->total_transactions,
                'total_amount' => $todaySales->total_amount,
                'total_items' => $todaySales->total_items,
                'date' => Carbon::today()->format('Y-m-d')
            ],
            'priority' => 'low',
            'created_at' => now()
        ];
    }

    public function getAllNotifications()
    {
        $notifications = [];

        // Gabungkan semua notifikasi
        $notifications = array_merge($notifications, $this->getOutOfStockNotifications());
        $notifications = array_merge($notifications, $this->getLowStockNotifications());
        $notifications = array_merge($notifications, $this->getExpiringProductNotifications());

        // Urutkan berdasarkan prioritas dan waktu
        usort($notifications, function ($a, $b) {
            $priorityOrder = ['high' => 3, 'medium' => 2, 'low' => 1];

            $priorityA = $priorityOrder[$a['priority']] ?? 1;
            $priorityB = $priorityOrder[$b['priority']] ?? 1;

            if ($priorityA === $priorityB) {
                return $b['created_at'] <=> $a['created_at'];
            }

            return $priorityB <=> $priorityA;
        });

        return [
            'success' => true,
            'data' => $notifications,
            'total' => count($notifications)
        ];
    }
}
