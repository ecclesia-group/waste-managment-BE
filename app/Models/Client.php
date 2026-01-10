<?php
namespace App\Models;

class Client extends Actor
{
    protected $fillable = [
        'client_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'email_verified_at',
        'gps_address',
        'type',
        'pickup_location',
        'bin_size',
        'bin_code',
        'status',
        'group_id',
        'qrcode',
        'profile_image',
    ];

    protected $hidden = [
        "password",
        "created_at",
        "updated_at",
        " ",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        'deleted_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        "password"          => "hashed",
        "profile_image"     => "array",
        "qrcode"            => "array",
    ];

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'client_slug', 'client_slug');
    }

    public function pickups()
    {
        return $this->hasMany(Pickup::class, 'client_slug', 'client_slug');
    }

    // public function notifications()
    // {
    //     return $this->hasMany(Notification::class, 'actor_id', 'actor_slug')->where('actor', 'client');
    // }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'actor', 'actor', 'actor_slug', 'actor_id');
    }

    public function getRouteKeyName(): string
    {
        return "client_slug";
    }
}
