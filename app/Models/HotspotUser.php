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
     * The actual RADIUS username synced to radcheck for this account — NOT always
     * phone_number. Three schemes depending on how the row originated:
     *   - Auto-purchased (BillingController::activateHotspotUser()): the M-Pesa receipt of
     *     the successful Transaction that created this row — shown on the router's own
     *     active-users list, and what the customer re-enters at the captive portal.
     *   - Voucher (VoucherController::generate()): the voucher code, stored directly in
     *     phone_number — no linked Transaction, so this falls through to the phone_number
     *     branch below naturally.
     *   - Manually created (HotspotUserController::store()/update()): whatever was typed
     *     into the phone_number field, treated as a plain username — also no linked
     *     Transaction, same fallthrough.
     * Used anywhere radacct/radcheck needs to be matched back to this row (dashboard/report
     * "online now" counts, connection logs, self-service re-login) instead of assuming
     * phone_number is the credential.
     */
    public function radiusUsername(): string
    {
        $receipt = $this->transactions()
            ->withoutGlobalScope('tenant')
            ->where('status', 'success')
            ->latest()
            ->value('mpesa_receipt');

        return $receipt ?: $this->phone_number;
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