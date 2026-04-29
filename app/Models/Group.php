<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'group_slug',
        'provider_slug',
        // 'zones',
        // 'locations',
        'description',
        'status',
    ];

    protected $casts = [
        // 'zones'      => 'array',
        // 'locations'  => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return "group_slug";
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function clients()
    {
        return $this->belongsToMany(
            Client::class,
            'client_groups',
            'group_slug',
            'client_slug',
            'group_slug',
            'client_slug'
        )
            ->withPivot(['provider_slug'])
            ->withTimestamps();
    }
}
