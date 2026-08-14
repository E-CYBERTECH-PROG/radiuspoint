<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'alert_type', 'channels'];

    protected $casts = ['channels' => 'array'];

    /**
     * Default channels when a user has no preference set. SMS and webpush stay
     * opt-in since they need extra setup (provider credentials / browser permission).
     */
    public static function defaultChannels(): array
    {
        return ['mail', 'database'];
    }

    public static function channelsFor(User $user, string $alertType): array
    {
        $pref = static::where('user_id', $user->id)->where('alert_type', $alertType)->first();

        return $pref ? $pref->channels : static::defaultChannels();
    }

    /**
     * Maps channel names to their notification class. Built-in channels stay as strings.
     */
    public static function resolveChannelClasses(array $channels): array
    {
        return array_map(fn ($channel) => match ($channel) {
            'sms' => \App\Notifications\Channels\SmsChannel::class,
            'webpush' => \NotificationChannels\WebPush\WebPushChannel::class,
            default => $channel,
        }, $channels);
    }
}
