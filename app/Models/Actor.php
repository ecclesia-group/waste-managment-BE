<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;

class Actor extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    // public function hasVerifiedEmail()
    // {
    //     return $this->email_verified_at != null;
    // }
}
