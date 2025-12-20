<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'actor',
        'actor_id',
        'actor_slug',
        'title',
        'message',
        'type',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function actor()
    {
        return $this->morphTo(__FUNCTION__, 'actor', 'actor_slug', 'actor_id');
    }
}
