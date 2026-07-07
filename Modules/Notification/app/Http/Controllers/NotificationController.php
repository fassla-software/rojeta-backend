<?php

namespace Modules\Notification\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get Notifications
     */
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($notif) {
                return [
                    "id" => $notif->id,
                    "title" => $notif->title,
                    "body" => $notif->body,
                    "type" => $notif->type,
                    "time_ago" => $notif->created_at->diffForHumans(),
                    "is_read" => (bool) $notif->is_read,
                    "is_pinned" => (bool) $notif->is_pinned
                ];
            });

        return response()->json($notifications);
    }

    /**
     * Mark all as read
     */
    public function markAllRead(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->update(['is_read' => true]);

        return response()->json([
            "success" => true,
            "message" => "All notifications marked as read"
        ]);
    }

    /**
     * Mark single notification as read
     */
    public function markRead(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        $notif = Notification::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
            
        $notif->update(['is_read' => true]);

        return response()->json([
            "id" => $notif->id,
            "is_read" => true
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id' => 'required|integer'
        ]);

        Notification::where('id', $request->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        return response()->json([
            "success" => true,
            "message" => "Notification deleted successfully"
        ]);
    }
}
