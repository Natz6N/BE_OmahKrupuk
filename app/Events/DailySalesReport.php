<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DailySalesReport implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reportData;
    public $reportDate;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(array $reportData, $reportDate = null)
    {
        $this->reportData = $reportData;
        $this->reportDate = $reportDate ?? now()->format('Y-m-d');
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('daily-reports'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'report.daily-sales';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'report_date' => $this->reportDate,
            'total_transactions' => $this->reportData['total_transactions'] ?? 0,
            'total_amount' => $this->reportData['total_amount'] ?? 0,
            'total_items' => $this->reportData['total_items'] ?? 0,
            'avg_transaction' => $this->reportData['avg_transaction'] ?? 0,
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "Daily sales report for {$this->reportDate}: {$this->reportData['total_transactions']} transactions, Rp " . number_format($this->reportData['total_amount'], 0, ',', '.')
        ];
    }
}
