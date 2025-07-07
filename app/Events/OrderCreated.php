<?php

namespace App\Events;

use App\Models\PendingOrder;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $userId;
    public $items;
    public $groupKey;
    public $socketId;
    /**
     * Create a new event instance.
     */
    public function __construct($userId, $items, $groupKey, $socketId = null)
    {
        $this->userId = $userId;
        $this->items = $items;
        $this->groupKey = $groupKey;
         $this->socketId = $socketId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new Channel("ordersItem");
    }

    public function broadcastWith()
    {
        return [
            'userId' => $this->userId,
            'groupKey' => $this->groupKey,
            'items' => $this->items,
            'socketId' => $this->socketId
        ];
    }
}
