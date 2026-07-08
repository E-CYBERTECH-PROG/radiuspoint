<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'alert_type', 'channels'];

    protected $casts = ['channels' => 'array'];

    /**
     * Channels for a user who hasn't customized this alert type yet. Mail+database are
     * always-on infra that already works; sms/webpush stay opt-in since they need extra setup
     * (provider credentials / browser permission) before they can actually deliver anything.
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
     * Laravel's built-in channels ('mail', 'database') are referenced by string; custom
     * channels need their FQCN. Keeps that mapping in one place rather than repeated in every
     * Notification class's via().
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
