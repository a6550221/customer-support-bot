<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TypingIndicator implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $sessionId,
        public string $senderType,
        public string $senderName
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('chat.' . $this->sessionId)];
    }

    public function broadcastAs(): string
    {
        return 'typing';
    }
}
