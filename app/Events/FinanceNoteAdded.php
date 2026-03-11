<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FinanceNoteAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $projectName;
    public $noteableType;  // 'noc', 'pop', 'soa', 'penalty', 'thirdparty'
    public $noteableId;
    public $note;

    public function __construct(string $projectName, string $noteableType, int $noteableId, array $note)
    {
        $this->projectName  = $projectName;
        $this->noteableType = $noteableType;
        $this->noteableId   = $noteableId;
        $this->note         = $note;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('finance.' . str_replace(' ', '-', strtolower($this->projectName))),
        ];
    }

    public function broadcastAs(): string
    {
        return 'note.added';
    }

    public function broadcastWith(): array
    {
        return [
            'noteableType' => $this->noteableType,
            'noteableId'   => $this->noteableId,
            'note'         => $this->note,
            'timestamp'    => now()->toIso8601String(),
        ];
    }
}
