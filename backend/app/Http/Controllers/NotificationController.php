<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use App\Events\NotificationSent;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Crée une notification ET la diffuse en temps réel via Pusher.
     */
    public static function createAndBroadcast(int $userId, string $message, int $ticketId): void
    {
        $notification = \App\Models\Notification::create([
            'user_id'   => $userId,
            'message'   => $message,
            'ticket_id' => $ticketId,
            'lu'        => false,
        ]);

        broadcast(new NotificationSent($notification));
    }

    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::with('ticket:id,project_id')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications, 200);
    }

    public function unreadCount()
    {
        $count = Notification::where('user_id', Auth::id())
            ->where('lu', false)
            ->count();

        return response()->json(['count' => $count], 200);
    }

    public function markAsRead(Request $request)
    {
        $user = Auth::user();
        
        $validated = $request->validate([
            'notification_ids' => 'required|array',
            'notification_ids.*' => 'exists:notifications,id'
        ]);

        Notification::whereIn('id', $validated['notification_ids'])
            ->where('user_id', $user->id)
            ->update(['lu' => true]);

        return response()->json(['message' => 'Notifications marquées comme lues'], 200);
    }
}