<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'product_slug',
        'name',
        'description',
        'price',
        'stock_quantity',
        'status',
        'category_id',
        'size',
        'images',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'images'     => 'array',
    ];
}
