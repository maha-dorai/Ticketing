<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification)
    {}

    /**
     * Chaque utilisateur écoute son propre channel privé :
     * private-user.{userId}
     */
    public function broadcastOn(): Channel
    {
        return new PrivateChannel("user.{$this->notification->user_id}");
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }

    public function broadcastWith(): array
    {
        return [
            'id'         => $this->notification->id,
            'message'    => $this->notification->message,
            'ticket_id'  => $this->notification->ticket_id,
            'lu'         => false,
            'created_at' => $this->notification->created_at,
        ];
    }
}