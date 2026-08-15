<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

class Plan extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Single source of truth for duration units, used by validation and dropdowns.
     * addDurationTo() validates against this list explicitly rather than defaulting.
     */
    public const DURATION_UNITS = ['minutes', 'hours', 'days', 'weeks', 'months'];

    protected $fillable = [
    'tenant_id',
    'name',
    'type',
    'price',
    'duration_value',
    'duration_unit',
    'data_cap_mb',
    'speed_limit',
    'caption',
    'fup_speed_limit',
];

    /**
     * Routers this plan is restricted to. Empty means "applies to every active router" — see
     * PlanReconcile, which is the only place this restriction is actually enforced.
     */
    public function routers(): BelongsToMany
    {
        return $this->belongsToMany(Router::class);
    }

    public function expiresAt(): Carbon
    {
        return $this->addDurationTo(now());
    }

    /**
     * Plain-language label derived from the download side of speed_limit (rx/tx, e.g. "5M/5M").
     * Returns null if speed_limit isn't in that format.
     */
    public function speedTierLabel(): ?string
    {
        if (! preg_match('/^(\d+)([kKmM])/', $this->speed_limit ?? '', $m)) {
            return null;
        }

        $mbps = strtolower($m[2]) === 'k' ? ((int) $m[1]) / 1000 : (int) $m[1];

        return match (true) {
            $mbps >= 8 => 'Great for HD streaming',
            $mbps >= 3 => 'Good for browsing & social media',
            default => 'Basic browsing & messaging',
        };
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
            'days' => $start->addDays($value),
            'weeks' => $start->addWeeks($value),
            'months' => $start->addMonths($value),
            default => throw new \InvalidArgumentException(
                "Plan #{$this->id} has an unrecognized duration_unit '{$this->duration_unit}' — ".
                'add it to Plan::DURATION_UNITS and this match() explicitly rather than letting it '.
                'silently fall through to a default duration.'
            ),
        };
    }
}