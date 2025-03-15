<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ScanToKitchen implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $scanId;
    /**
     * Create a new event instance.
     */
    public function __construct($scanId)
    {
        $this->scanId = $scanId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel("Scan-Kitchen");
    }
}
