<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'actor',
        'actor_id',
        'transaction_id',
        'payment_method',
        'network',
        'phone_number',
        'client_email',
        'card_name',
        'card_number',
        'card_expiry',
        'card_cvv',
        'amount',
        'currency',
        'status',
    ];

    protected $casts = [
        'amount'     => 'float',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
