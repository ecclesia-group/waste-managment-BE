<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

    class Cart extends Model
{
    protected $with = [
        'client',
    ];

    use SoftDeletes;

    protected $fillable = [
        'client_slug',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }
}

