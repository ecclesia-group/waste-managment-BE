<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $fillable = [
        'code',
        'client_slug',
        'ratings',
        'comments',
        'status',
    ];
}
