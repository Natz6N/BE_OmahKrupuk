<?php

namespace App\Listeners;

use App\Events\SaleCompleted;
use App\Events\DailySalesReport;
use App\Models\Sale;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SaleCompletedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(SaleCompleted $event): void
    {
        Log::info('Sale completed event received', [
            'sale_id' => $event->sale->id,
            'invoice_number' => $event->sale->invoice_number,
            'total_amount' => $event->sale->total_amount,
            'cashier' => $event->sale->user->name
        ]);

        // Update daily statistics (could be cached)
        $this->updateDailyStats($event->sale);

        // Log for audit trail
        Log::channel('sales')->info('Sale completed', [
            'sale_id' => $event->sale->id,
            'invoice_number' => $event->sale->invoice_number,
            'total_amount' => $event->sale->total_amount,
            'items' => $event->sale->items->count(),
            'cashier_id' => $event->sale->user_id,
            'timestamp' => $event->timestamp
        ]);
    }

    private function updateDailyStats(Sale $sale)
    {
        // This could update cached statistics or trigger other processes
        $today = Carbon::today();
        $todayStats = Sale::whereDate('created_at', $today)
                         ->selectRaw('COUNT(*) as total_transactions, SUM(total_amount) as total_amount, SUM(total_items) as total_items')
                         ->first();

        // Broadcast updated daily stats every 10th sale or significant milestones
        if ($todayStats->total_transactions % 10 === 0) {
            event(new DailySalesReport([
                'total_transactions' => $todayStats->total_transactions,
                'total_amount' => $todayStats->total_amount,
                'total_items' => $todayStats->total_items,
                'avg_transaction' => $todayStats->total_transactions > 0 ? $todayStats->total_amount / $todayStats->total_transactions : 0
            ]));
        }
    }
}
