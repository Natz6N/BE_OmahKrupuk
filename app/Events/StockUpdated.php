<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $productVariantId;
    public $newQuantity;
    public $movementType;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct($productVariantId, $newQuantity, $movementType)
    {
        $this->productVariantId = $productVariantId;
        $this->newQuantity = $newQuantity;
        $this->movementType = $movementType;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('stock-updates'),
            new PrivateChannel('admin-notifications'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'stock.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'product_variant_id' => $this->productVariantId,
            'new_quantity' => $this->newQuantity,
            'movement_type' => $this->movementType,
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "Stock updated for product variant {$this->productVariantId}"
        ];
    }
}
