<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToTenant;

class SmsTrigger extends Model
{
    use BelongsToTenant;

    /**
     * Lifecycle events a tenant can wire an SmsTemplate to. `pppoe_payment_receipt`
     * is reserved for future use; PPPoE renewals are currently recorded manually.
     */
    public const KEYS = [
        'pppoe_expiry_reminder_3d',
        'pppoe_expired',
        'pppoe_renewed',
        'pppoe_welcome',
        'pppoe_payment_receipt',
        'hotspot_purchase_confirmed',
        'hotspot_voucher_created',
    ];

    protected $fillable = [
        'tenant_id',
        'trigger_key',
        'sms_template_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(SmsTemplate::class, 'sms_template_id');
    }
}
