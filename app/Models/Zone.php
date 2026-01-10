<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'zone_slug',
        'region',
        'description',
        'locations',
        'status',
    ];

    protected $casts = [
        'locations'  => 'array',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getRouteKeyName(): string
    {
        return "zone_slug";
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'zone_slug', 'zone_slug');
    }
}
