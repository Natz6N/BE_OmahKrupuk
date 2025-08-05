<?php
namespace App\Observers;

use App\Models\StockMovement;
use App\Models\CurrentStock;
use App\Events\StockUpdated;
use App\Events\LowStockAlert;

class StockMovementObserver
{
    /**
     * Handle the StockMovement "created" event.
     */
    public function created(StockMovement $stockMovement): void
    {
        $this->updateCurrentStock($stockMovement);
    }

    /**
     * Handle the StockMovement "updated" event.
     */
    public function updated(StockMovement $stockMovement): void
    {
        // Jika quantity berubah, update current stock
        if ($stockMovement->wasChanged('quantity')) {
            $this->updateCurrentStock($stockMovement);
        }
    }

    /**
     * Handle the StockMovement "deleted" event.
     */
    public function deleted(StockMovement $stockMovement): void
    {
        // Reverse the stock movement
        $this->reverseStockMovement($stockMovement);
    }

    /**
     * Update current stock based on stock movement
     */
    private function updateCurrentStock(StockMovement $stockMovement)
    {
        $currentStock = CurrentStock::firstOrCreate(
            ['product_variant_id' => $stockMovement->product_variant_id],
            [
                'quantity' => 0,
                'min_stock' => 5,
                'avg_purchase_price' => 0
            ]
        );

        // Update quantity
        $newQuantity = max(0, $currentStock->quantity + $stockMovement->quantity);

        // Update average purchase price if it's a stock in with price
        $newAvgPrice = $currentStock->avg_purchase_price;
        if ($stockMovement->type === 'in' && $stockMovement->purchase_price && $stockMovement->quantity > 0) {
            $totalValue = ($currentStock->quantity * $currentStock->avg_purchase_price) +
                         ($stockMovement->quantity * $stockMovement->purchase_price);
            $newAvgPrice = $newQuantity > 0 ? $totalValue / $newQuantity : 0;
        }

        $currentStock->update([
            'quantity' => $newQuantity,
            'avg_purchase_price' => $newAvgPrice
        ]);

        // Trigger events
        event(new StockUpdated(
            $stockMovement->product_variant_id,
            $newQuantity,
            $stockMovement->type
        ));

        // Check for low stock
        if ($newQuantity <= $currentStock->min_stock && $newQuantity > 0) {
            event(new LowStockAlert(
                $stockMovement->productVariant,
                $newQuantity
            ));
        }
    }

    /**
     * Reverse stock movement (when deleted)
     */
    private function reverseStockMovement(StockMovement $stockMovement)
    {
        $currentStock = CurrentStock::where('product_variant_id', $stockMovement->product_variant_id)->first();

        if ($currentStock) {
            $newQuantity = max(0, $currentStock->quantity - $stockMovement->quantity);
            $currentStock->update(['quantity' => $newQuantity]);

            event(new StockUpdated(
                $stockMovement->product_variant_id,
                $newQuantity,
                'reversed'
            ));
        }
    }
}
