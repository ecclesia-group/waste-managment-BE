<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Violation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'client_slug',
        'provider_slug',
        'type',
        'status',
        'location',
        'description',
        'images',
        'videos',
    ];

    protected $casts = [
        'images'     => 'array',
        'videos'     => 'array',
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

    public function getRouteKeyName(): string
    {
        return "code";
    }
}
