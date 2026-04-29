<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bin extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'bin_slug',
        'bin_code',
        'client_slug',
        'provider_slug',
        'product_slug',
        'source',
        'status',
    ];

    protected $casts = [
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_slug', 'product_slug');
    }

    public function getRouteKeyName(): string
    {
        return 'bin_slug';
    }
}
