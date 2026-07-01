<?php
namespace App\Models;

use App\Traits\ScopesProviderOrganisation;

class Client extends Actor
{
    use ScopesProviderOrganisation;

    protected $with = [
        'group',
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
        'group_slug',
        'registration_fee',
        'registration_status',
        'fee_id',
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

    public function requiresRegistrationPayment(): bool
    {
        $fee = (float) ($this->registration_fee ?? 0);

        return $fee > 0 && ! (bool) $this->registration_status;
    }

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

    public function fee()
    {
        return $this->belongsTo(ProviderFee::class, 'fee_id');
    }

    public function getRouteKeyName(): string
    {
        return "client_slug";
    }

    public function group()
    {
        return $this->belongsTo(Group::class, 'group_slug', 'group_slug');
    }

    public function bins()
    {
        return $this->hasMany(Bin::class, 'client_slug', 'client_slug');
    }

    public function activeBins()
    {
        return $this->bins()->where('status', Bin::STATUS_ACTIVE);
    }

    public function registrationBin(): ?Bin
    {
        return $this->bins()
            ->where('source', Bin::SOURCE_REGISTRATION)
            ->orderByDesc('id')
            ->first();
    }

    public function primaryBin(): ?Bin
    {
        return $this->activeBins()->orderByDesc('id')->first();
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
        return $this->primaryBin()?->product?->size;
    }

    public function getCoordinatesAttribute(): array
    {
        return [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];
    }
}
