<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'nullable|string',
            'keys.auth' => 'nullable|string',
        ]);

        Auth::user()->updatePushSubscription(
            $request->input('endpoint'),
            $request->input('keys.p256dh'),
            $request->input('keys.auth'),
        );

        return response()->json(['status' => 'ok']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        Auth::user()->deletePushSubscription($request->input('endpoint'));

        return response()->json(['status' => 'ok']);
    }
}
