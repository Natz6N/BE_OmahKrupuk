<?php
namespace App\Listeners;

use App\Events\StockUpdated;
use App\Events\LowStockAlert;
use App\Models\CurrentStock;
use App\Models\ProductVariant;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class StockUpdatedListener implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(StockUpdated $event): void
    {
        Log::info('Stock updated event received', [
            'product_variant_id' => $event->productVariantId,
            'new_quantity' => $event->newQuantity,
            'movement_type' => $event->movementType
        ]);

        // Check for low stock and trigger alert if needed
        $currentStock = CurrentStock::where('product_variant_id', $event->productVariantId)->first();

        if ($currentStock && $currentStock->quantity <= $currentStock->min_stock && $currentStock->quantity > 0) {
            $variant = ProductVariant::with('product')->find($event->productVariantId);
            if ($variant) {
                event(new LowStockAlert($variant, $currentStock->quantity));
            }
        }
    }
}
