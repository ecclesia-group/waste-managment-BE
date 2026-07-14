<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Notification extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'actor',
        'actor_slug',
        'admin_slug',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'deleted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(Admin::class, 'admin_slug', 'admin_slug');
    }

    public function getRouteKeyName(): string
    {
        return "id";
    }
}
