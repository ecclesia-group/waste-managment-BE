<?php

namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use ScopesProviderOrganisation, SoftDeletes;

    public const SOURCE_ASSIGNED = 'assigned';

    public const SOURCE_PURCHASE = 'purchase';

    public const SOURCE_MANUAL = 'manual';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'item_code',
        'client_slug',
        'provider_slug',
        'product_slug',
        'purchase_id',
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
        return 'item_code';
    }
}
