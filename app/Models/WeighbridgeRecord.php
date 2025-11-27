<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeighbridgeRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'client_id',
        'amount',
        'group_id',
    ];
}
