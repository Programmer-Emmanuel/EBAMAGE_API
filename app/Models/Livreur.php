<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Livreur extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom_liv',
        'pren_liv',
        'email_liv',
        'tel_liv',
        'photo_liv',
        'photo_cni',
        'photo_permis',
        'password_liv',
        'solde_tdl',
        'code_otp',
        'otp_expires_at'
    ];

        protected $hidden = [
        'password_liv',
        'code_otp',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password_liv' => 'hashed',
        ];
    }
}
