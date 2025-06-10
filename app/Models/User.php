<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Vinkla\Hashids\Facades\Hashids;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'nom_clt',
        'pren_clt',
        'email_clt',
        'tel_clt',
        'image_clt',
        'password_clt',
        'solde_tdl',
        'code_otp',
        'otp_expires_at',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'id',
        'password_clt',
        'code_otp',
        'remember_token',
    ];

    //Proteger les dates
    protected $dates = ['otp_expires_at', 'email_verified_at'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password_clt' => 'hashed',
        ];
    }
}
