<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PendingCountsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $developerEmail;
    public $counts;

    /**
     * Create a new event instance.
     */
    public function __construct($developerEmail, $counts)
    {
        $this->developerEmail = $developerEmail;
        $this->counts = $counts;
    }
/* */
    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Use sanitized email for channel name (replace @ and . with -)
        $channelName = 'developer.' . str_replace(['@', '.'], '-', $this->developerEmail);
        return [
            new Channel($channelName),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'pending-counts.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'counts' => $this->counts,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
