<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
// use Illuminate\Support\Facades\Log;

class ReadSchema implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    const STATUSES = [
        'COMPLETED' => 'completed',
        'FAILED' => 'failed',
    ];

    /**
     * Create a new event instance.
     * @param int $userId
     * @param int $convertId
     * @param string $status
     */
    public function __construct(
        public int $userId,
        public int $convertId,
        public string $status,
    ) {
        //
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {  
        return new PrivateChannel('users.' . $this->userId .
            '.converts.' . $this->convertId .
            '.ReadSchema');
    }
}
