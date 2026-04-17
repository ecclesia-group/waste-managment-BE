<?php
namespace App\Models;

class Admin extends Actor
{
    protected $fillable = [
        'admin_slug',
        'parent_slug',
        'is_main',
        'role_slug',
        'first_name',
        'last_name',
        'phone_number',
        'email',
        'password',
        'email_verified_at',
        'profile_image',
        'status',
        'suspension_reason',
        'corrective_action',
        'suspended_at',
    ];

    protected $hidden = [
        "password",
        "deleted_at",
    ];

    protected $casts = [
        "email_verified_at" => "datetime",
        'deleted_at'        => 'datetime',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
        'is_main'           => 'boolean',
        "password"          => "hashed",
        "profile_image"     => "array",
        "status"            => "string",
        "suspension_reason" => "string",
        "corrective_action" => "string",
        "suspended_at"      => "datetime",
    ];
}
