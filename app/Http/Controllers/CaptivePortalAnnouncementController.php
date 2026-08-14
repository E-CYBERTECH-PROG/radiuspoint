<?php

namespace App\Http\Controllers;

use App\Models\CaptivePortalAnnouncement;
use App\Models\Router;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CaptivePortalAnnouncementController extends Controller
{
    public function index()
    {
        $announcements = CaptivePortalAnnouncement::where('tenant_id', Auth::user()->tenant_id)
            ->with('router')
            ->latest()
            ->get();
        $routers = Router::where('tenant_id', Auth::user()->tenant_id)->get();

        return view('captive-portal-announcements.index', compact('announcements', 'routers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'router_id' => 'nullable|exists:routers,id',
            'category' => 'required|in:' . implode(',', CaptivePortalAnnouncement::CATEGORIES),
            'message' => 'required|string|max:280',
            'expires_at' => 'nullable|date',
        ]);

        CaptivePortalAnnouncement::create([
            'tenant_id' => Auth::user()->tenant_id,
            'router_id' => $request->router_id,
            'category' => $request->category,
            'message' => $request->message,
            'expires_at' => $request->expires_at,
        ]);

        return redirect()->route('captive-announcements.index')->with('success', 'Announcement posted.');
    }

    public function destroy(CaptivePortalAnnouncement $captive_announcement)
    {
        $captive_announcement->delete();

        return redirect()->route('captive-announcements.index')->with('success', 'Announcement removed.');
    }
}
