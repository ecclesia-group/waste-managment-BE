<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const PAYMENT_TYPE_REGISTRATION_FEE = 'registration_fee';

    public const STATUS_PAID = 'paid';

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
    ];

    protected $casts = [
        'amount'     => 'float',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
