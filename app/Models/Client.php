<?php
namespace App\Models;

class Client extends Actor
{
    protected $with = [
        'group',
        'bin',
        'provider'
    ];

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
        'status',
        'bin_slug',
        'group_slug',
        'zone_slug',
        'registration_fee',
        'registration_status',
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
        'latitude'          => 'float',
        'longitude'         => 'float',
        'registration_fee'  => 'float',
        'registration_status' => 'boolean',
    ];

    /**
     * If a paid registration_fee payment exists, set registration_status true (keeps client row in sync).
     */
    public function syncRegistrationStatusFromPayments(): void
    {
        $paid = Payment::query()
            ->where('client_slug', $this->client_slug)
            ->where('payment_type', Payment::PAYMENT_TYPE_REGISTRATION_FEE)
            ->where('status', Payment::STATUS_PAID)
            ->exists();

        if ($paid && ! $this->registration_status) {
            $this->registration_status = true;
            $this->save();
        }
    }

    // public function requiresRegistrationPayment(): bool
    // {
    //     $fee = (float) ($this->registration_fee ?? 0);

    //     return $fee > 0 && ! (bool) $this->registration_status;
    // }

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

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'actor', 'actor', 'actor_id', 'id');
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

    public function bin()
    {
        return $this->belongsTo(Bin::class, 'bin_slug', 'bin_slug');
    }

    public function bins()
    {
        return $this->hasMany(Bin::class, 'client_slug', 'client_slug');
    }

    public function primaryBin(): ?Bin
    {
        if ($this->relationLoaded('bin') && $this->bin) {
            return $this->bin;
        }

        if ($this->bin_slug) {
            return $this->bin()->first();
        }

        return $this->bins()->where('status', 'active')->orderByDesc('id')->first();
    }

    public function getBinCodeAttribute(): ?string
    {
        return $this->primaryBin()?->bin_code;
    }

    public function getPickupLocationAttribute(): ?string
    {
        return $this->gps_address;
    }

    public function getBinSizeAttribute(): ?string
    {
        $bin = $this->primaryBin();

        return $bin?->product?->size;
    }

    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
