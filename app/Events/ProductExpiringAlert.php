<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\StockMovement;
use Carbon\Carbon;

class ProductExpiringAlert implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $stockMovement;
    public $daysUntilExpiry;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(StockMovement $stockMovement)
    {
        $this->stockMovement = $stockMovement;
        $this->daysUntilExpiry = (int) Carbon::now()->diffInDays($stockMovement->expired_date, false);
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('expiry-alerts'),
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('inventory-alerts'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'product.expiring';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'stock_movement_id' => $this->stockMovement->id,
            'product_variant_id' => $this->stockMovement->product_variant_id,
            'product_name' => $this->stockMovement->productVariant->product->name,
            'variant_name' => $this->stockMovement->productVariant->variant_name,
            'full_name' => $this->stockMovement->productVariant->full_name,
            'batch_number' => $this->stockMovement->batch_number,
            'expired_date' => $this->stockMovement->expired_date->format('Y-m-d'),
            'days_until_expiry' => $this->daysUntilExpiry,
            'quantity' => $this->stockMovement->quantity,
            'priority' => $this->daysUntilExpiry <= 7 ? 'urgent' : ($this->daysUntilExpiry <= 30 ? 'warning' : 'info'),
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "Product expiring soon: {$this->stockMovement->productVariant->full_name} will expire in {$this->daysUntilExpiry} days"
        ];
    }
}
