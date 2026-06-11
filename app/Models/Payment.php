<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const PAYMENT_TYPE_REGISTRATION_FEE = 'registration_fee';
    public const PAYMENT_TYPE_BULK_WASTE = 'bulk_waste_request';
    public const PAYMENT_TYPE_PICKUP = 'pickup';
    public const PAYMENT_TYPE_PURCHASE = 'purchase';
    public const PAYMENT_TYPE_HANDOVER = 'waste_handover';
    public const PAYMENT_TYPE_WEIGHBRIDGE = 'weighbridge';

    public const STATUS_PAID = 'paid';
    public const STATUS_SUCCESSFUL = 'successful';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    protected $with = [
        'client',
    ];

    protected $fillable = [
        'client_slug',
        'provider_slug',
        'payment_type',
        'transaction_id',
        'payment_method',
        'network',
        'phone_number',
        'name',
        'client_email',
        'card_name',
        'card_number',
        'card_expiry',
        'card_cvv',
        'amount',
        'currency',
        'status',
        'purchase_id',
        'pickup_id',
        'calpay_order_code',
        'payable_reference',
        'gateway_payload',
        'callback_payload',
    ];

    protected $casts = [
        'amount'     => 'float',
        'gateway_payload' => 'array',
        'callback_payload' => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id', 'id');
    }

    public function pickup()
    {
        return $this->belongsTo(Pickup::class, 'pickup_id', 'id');
    }

    public function getRouteKeyName(): string
    {
        return 'transaction_id';
    }
}
