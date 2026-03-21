<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_slug',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}

