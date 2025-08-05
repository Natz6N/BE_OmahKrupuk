<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\Sale;

class SaleCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sale;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('sales-updates'),
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('cashier-updates'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sale.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'sale_id' => $this->sale->id,
            'invoice_number' => $this->sale->invoice_number,
            'total_amount' => $this->sale->total_amount,
            'total_items' => $this->sale->total_items,
            'payment_method' => $this->sale->payment_method,
            'cashier' => [
                'id' => $this->sale->user->id,
                'name' => $this->sale->user->name
            ],
            'items_count' => $this->sale->items->count(),
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "New sale completed: {$this->sale->invoice_number} - Rp " . number_format($this->sale->total_amount, 0, ',', '.')
        ];
    }
}
