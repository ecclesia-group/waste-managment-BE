<?php
namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use ScopesProviderOrganisation;

    public const CATEGORY_BIN = 'bin';

    public const CATEGORY_WASTE_ITEM = 'waste_item';
    protected $with = [
        'provider',
    ];

    use SoftDeletes;

    protected $fillable = [
        'product_slug',
        'provider_slug',
        'name',
        'category',
        'color',
        'size',
        'images',
        'original_price',
        'discounted_price',
        'discount_percentage',
        'quantity',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'images'     => 'array',
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function items()
    {
        return $this->hasMany(Item::class, 'product_slug', 'product_slug');
    }

    public function getRouteKeyName(): string
    {
        return "product_slug";
    }
}
