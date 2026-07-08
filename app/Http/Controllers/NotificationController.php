<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Full paginated history — the bell dropdown only ever shows the latest 10.
     */
    public function index()
    {
        $notifications = Auth::user()->notifications()->paginate(30);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * Polled by the sidebar bell every 20s so the badge count and list actually reflect new
     * alerts arriving while the admin is browsing, instead of only ever reflecting whatever was
     * true at the last full page load.
     */
    public function recent()
    {
        $user = Auth::user();

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $user->notifications()->latest()->limit(10)->get()->map(fn ($n) => [
                'id' => $n->id,
                'message' => $n->data['message'] ?? 'Notification',
                'url' => $n->data['url'] ?? null,
                'read' => (bool) $n->read_at,
                'created_at_human' => $n->created_at->diffForHumans(),
            ]),
        ]);
    }

    public function markRead(string $id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();
        $notification?->markAsRead();

        return response()->json(['status' => 'ok']);
    }

    public function markAllRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['status' => 'ok']);
    }
}
