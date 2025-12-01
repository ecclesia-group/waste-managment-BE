<?php
namespace App\Models;

use App\Traits\Helpers;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;

class Actor extends Authenticatable implements MustVerifyEmail, OAuthenticatable
{
    use HasFactory, SoftDeletes, HasApiTokens, Notifiable, Helpers;

}
