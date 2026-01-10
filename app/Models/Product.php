<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

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
        'deleted_at' => 'datetime',
    ];
}
