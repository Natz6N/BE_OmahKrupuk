<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $activity;
    public $description;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $activity, string $description = null)
    {
        $this->user = $user;
        $this->activity = $activity;
        $this->description = $description;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin-notifications'),
            new PrivateChannel('user-activities'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'user.activity';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->user->id,
            'user_name' => $this->user->name,
            'user_role' => $this->user->role,
            'activity' => $this->activity,
            'description' => $this->description,
            'timestamp' => $this->timestamp->toISOString(),
            'message' => "{$this->user->name} {$this->activity}" . ($this->description ? ": {$this->description}" : "")
        ];
    }
}
