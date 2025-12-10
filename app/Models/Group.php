<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = [
        'name',
        'group_slug',
        'zones',
        'description',
    ];

    protected $casts = [
        'zones' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return "group_slug";
    }
}
