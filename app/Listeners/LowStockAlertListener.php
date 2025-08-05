<?php
namespace App\Listeners;

use App\Events\LowStockAlert;
use App\Events\SystemAlert;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class LowStockAlertListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(LowStockAlert $event): void
    {
        Log::warning('Low stock alert triggered', [
            'product_variant_id' => $event->productVariant->id,
            'product_name' => $event->productVariant->full_name,
            'current_quantity' => $event->currentQuantity,
            'min_stock' => $event->minStock
        ]);

        // Trigger system alert for critical stock levels
        if ($event->currentQuantity <= 0) {
            event(new SystemAlert(
                'out_of_stock',
                'Stok Habis',
                "Produk {$event->productVariant->full_name} sudah habis!",
                'critical',
                [
                    'product_variant_id' => $event->productVariant->id,
                    'product_name' => $event->productVariant->full_name,
                    'current_quantity' => $event->currentQuantity
                ]
            ));
        } elseif ($event->currentQuantity <= ($event->minStock * 0.5)) {
            event(new SystemAlert(
                'very_low_stock',
                'Stok Sangat Menipis',
                "Produk {$event->productVariant->full_name} sangat menipis ({$event->currentQuantity} {$event->productVariant->unit})",
                'warning',
                [
                    'product_variant_id' => $event->productVariant->id,
                    'product_name' => $event->productVariant->full_name,
                    'current_quantity' => $event->currentQuantity,
                    'min_stock' => $event->minStock
                ]
            ));
        }

        // Log to special low stock channel
        Log::channel('inventory')->warning('Low stock alert', [
            'product_variant_id' => $event->productVariant->id,
            'product_name' => $event->productVariant->full_name,
            'current_quantity' => $event->currentQuantity,
            'min_stock' => $event->minStock,
            'timestamp' => $event->timestamp
        ]);
    }
}
