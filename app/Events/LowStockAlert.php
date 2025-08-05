<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\ProductVariant;

class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $productVariant;
    public $currentQuantity;
    public $minStock;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(ProductVariant $productVariant, $currentQuantity)
    {
        $this->productVariant = $productVariant;
        $this->currentQuantity = $currentQuantity;
        $this->minStock = $productVariant->currentStock->min_stock ?? 5;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('low-stock-alerts'),
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('inventory-alerts'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stock.low-alert';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_variant_id' => $this->productVariant->id,
            'product_name' => $this->productVariant->product->name,
            'variant_name' => $this->productVariant->variant_name,
            'full_name' => $this->productVariant->full_name,
            'current_quantity' => $this->currentQuantity,
            'min_stock' => $this->minStock,
            'unit' => $this->productVariant->unit,
            'priority' => $this->currentQuantity <= 0 ? 'critical' : 'warning',
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "Low stock alert: {$this->productVariant->full_name} has only {$this->currentQuantity} {$this->productVariant->unit} remaining"
        ];
    }
}
