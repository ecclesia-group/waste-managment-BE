<?php
namespace App\Models;

class Admin extends Actor
{
    protected $fillable = [
        'admin_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'email_verified_at',
        'profile_image',
    ];

    protected $hidden = [
        "password",
        "deleted_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        "password"          => "hashed",
        "profile_image"     => "array",
    ];
}
