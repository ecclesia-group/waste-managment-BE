<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_slug',
        'number_of_items',
        'total_price',
    ];

    protected $casts = [
        'number_of_items' => 'integer',
        'total_price' => 'decimal:2',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }

    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }
}
