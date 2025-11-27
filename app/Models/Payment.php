<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
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
}
