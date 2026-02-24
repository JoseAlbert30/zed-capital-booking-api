<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinancePOPUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $projectName;
    public $action; // 'created', 'receipt-uploaded', 'soa-requested', 'soa-uploaded', 'viewed', 'deleted'
    public $pop;

    /**
     * Create a new event instance.
     */
    public function __construct($projectName, $action, $pop = null)
    {
        $this->projectName = $projectName;
        $this->action = $action;
        $this->pop = $pop;
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
        return 'pop.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'pop' => $this->pop,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
