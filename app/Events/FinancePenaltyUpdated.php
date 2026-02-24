<?php

namespace App\Events;

use App\Models\FinancePenalty;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinancePenaltyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $projectName;
    public $action; // 'created', 'uploaded', 'viewed', 'deleted'
    public $penalty;

    /**
     * Create a new event instance.
     */
    public function __construct($projectName, $action, $penalty = null)
    {
        $this->projectName = $projectName;
        $this->action = $action;
        $this->penalty = $penalty;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('finance.' . str_replace(' ', '-', strtolower($this->projectName))),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'penalty.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'penalty' => $this->penalty,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
