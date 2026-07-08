<?php

namespace App\Http\Controllers;

use App\Enums\AlertType;
use App\Models\NotificationPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationPreferenceController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $alertTypes = AlertType::cases();
        $preferences = [];

        foreach ($alertTypes as $type) {
            $preferences[$type->value] = NotificationPreference::channelsFor($user, $type->value);
        }

        $vapidPublicKey = config('webpush.vapid.public_key');

        return view('settings.notifications', compact('alertTypes', 'preferences', 'vapidPublicKey'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        foreach (AlertType::cases() as $type) {
            $channels = array_values(array_intersect(
                $request->input("channels.{$type->value}", []),
                ['mail', 'database', 'sms', 'webpush']
            ));

            NotificationPreference::updateOrCreate(
                ['user_id' => $user->id, 'alert_type' => $type->value],
                ['channels' => $channels]
            );
        }

        return back()->with('status', 'notification-preferences-updated');
    }
}
