<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'zone_slug',
        'region',
        'description',
        'locations',
    ];

    protected $casts = [
        'locations' => 'array',
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
