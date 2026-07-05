<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

class Plan extends Model
{
    use BelongsToTenant;

    protected $fillable = [
    'tenant_id', // Make sure this is here!
    'name',
    'type',
    'price',
    'duration_value',
    'duration_unit',
    'data_cap_mb',
    'speed_limit'
];

    public function expiresAt(): Carbon
    {
        return $this->addDurationTo(now());
    }

    /**
     * Add this plan's validity duration to an arbitrary starting point — used both for
     * "expires N from now" (immediate activation) and "expires N from first connect"
     * (voucher activation-on-first-use).
     */
    public function addDurationTo(Carbon $start): Carbon
    {
        $value = $this->duration_value ?: 1;
        $start = $start->copy();

        return match ($this->duration_unit) {
            'minutes' => $start->addMinutes($value),
            'hours' => $start->addHours($value),
            'weeks' => $start->addWeeks($value),
            'months' => $start->addMonths($value),
            default => $start->addDays($value),
        };
    }
}