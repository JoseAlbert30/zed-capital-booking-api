<?php

namespace App\Events;

use App\Models\FinanceSOA;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinanceSOAUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $projectName;
    public $action; // 'created', 'uploaded', 'deleted'
    public $soa;

    /**
     * Create a new event instance.
     */
    public function __construct($projectName, $action, $soa = null)
    {
        $this->projectName = $projectName;
        $this->action = $action;
        $this->soa = $soa;
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
        return 'soa.updated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'soa' => $this->soa,
        ];
    }
}
