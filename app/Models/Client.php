<?php
namespace App\Models;

class Client extends Actor
{
    protected $fillable = [
        'client_slug',
        'provider_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'email_verified_at',
        'gps_address',
        'latitude',
        'longitude',
        'type',
        'pickup_location',
        'bin_size',
        'bin_code',
        'status',
        'group_slug',
        'qrcode',
        'profile_image',
    ];

    protected $hidden = [
        "password",
        "created_at",
        "updated_at",
        "deleted_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        'deleted_at'        => 'datetime',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
        "password"          => "hashed",
        "profile_image"     => "array",
        "qrcode"            => "array",
        'latitude'          => 'float',
        'longitude'         => 'float',
    ];

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'client_slug', 'client_slug');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class, 'client_slug', 'client_slug');
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

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_slug', 'provider_slug');
    }

    public function getRouteKeyName(): string
    {
        return "client_slug";
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_slug', 'group_slug');
    }
}
