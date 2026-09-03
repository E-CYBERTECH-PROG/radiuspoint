<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToTenant;

class HotspotUser extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * Single source of truth for valid statuses, used by validation rules and views.
     * Includes 'unused' for vouchers that have been generated but not yet redeemed.
     */
    public const STATUSES = ['unused', 'active', 'expired', 'offline'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'current_plan_id');
    }

    public function router(): BelongsTo
    {
        return $this->belongsTo(Router::class, 'current_router_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'hotspot_user_id');
    }

    /**
     * Every RADIUS username that's actually valid for this account right now — there can be
     * two for an auto-purchased account (see radiusUsername() below), otherwise just one.
     * Used anywhere radacct/radcheck needs to be matched back to this row (dashboard/report
     * "online now" counts, connection logs, live-status polling, usage/FUP accounting) or
     * revoked in full (expiry, disable, destroy, purge) — checking/touching only one when two
     * are valid would silently leave the other one working (or its usage uncounted).
     */
    public function radiusUsernames(): array
    {
        $receipt = $this->latestReceipt();

        return $receipt ? [$this->phone_number, $receipt] : [$this->phone_number];
    }

    /**
     * The single "primary" RADIUS username for this account — e.g. what's shown as "Username"
     * in the UI. Three schemes depending on how the row originated:
     *   - Auto-purchased (BillingController::activateHotspotUser()): the M-Pesa receipt of
     *     the successful Transaction that created this row — shown on the router's own
     *     active-users list, and what the automatic post-purchase reconnect uses. The account
     *     ALSO gets a standing phone_number+password credential at creation for later
     *     self-service logins (see radiusUsernames() above, and
     *     CaptivePortalController::lookup()) — this method just doesn't prefer it for display.
     *   - Voucher (VoucherController::generate()): the voucher code, stored directly in
     *     phone_number — no linked Transaction, so this falls through to the phone_number
     *     branch below naturally.
     *   - Manually created (HotspotUserController::store()/update()): whatever was typed
     *     into the phone_number field, treated as a plain username — also no linked
     *     Transaction, same fallthrough.
     */
    public function radiusUsername(): string
    {
        return $this->latestReceipt() ?: $this->phone_number;
    }

    private function latestReceipt(): ?string
    {
        return $this->transactions()
            ->withoutGlobalScope('tenant')
            ->where('status', 'success')
            ->latest()
            ->value('mpesa_receipt');
    }

    protected $fillable = [
        'tenant_id',
        'phone_number',
        'first_name',
        'last_name',
        'name',
        'email',
        'address',
        'mac_address',
        'current_plan_id',
        'current_router_id',
        'status',
        'is_voucher',
        'expires_at',
        'fup_throttled_at',
    ];

    protected $casts = [
        'is_voucher' => 'boolean',
        'expires_at' => 'datetime',
        'fup_throttled_at' => 'datetime',
    ];
}