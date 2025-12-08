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
        "deleted_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "password"          => "hashed",
        "profile_image"     => "array",
        "qrcode"            => "array",
    ];

    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'client_slug', 'client_slug');
    }
}
