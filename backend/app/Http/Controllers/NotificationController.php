<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = Notification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($notifications, 200);
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
