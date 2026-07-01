<?php

namespace App\Models;

use App\Traits\ScopesProviderOrganisation;
use Illuminate\Database\Eloquent\Model;

class ProviderFee extends Model
{
    use ScopesProviderOrganisation;

    protected $fillable = [
        'provider_slug',
        'name',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function clients()
    {
        return $this->hasMany(Client::class, 'fee_id');
    }
}
