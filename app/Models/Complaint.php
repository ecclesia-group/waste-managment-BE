<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_slug',
        'code',
        'location',
        'description',
        'status',
        'images',
        'videos',
    ];

    protected $casts = [
        'images' => 'array',
        'videos' => 'array',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class, 'client_slug', 'client_slug');
    }
}
