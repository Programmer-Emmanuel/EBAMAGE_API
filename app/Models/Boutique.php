<?php

namespace App\Models;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Vinkla\Hashids\Facades\Hashids;

class Boutique extends Authenticatable
{

    use HasApiTokens, HasFactory, Notifiable;

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];
    protected $hidden = ['id'];


    protected $fillable = [
        'nom_btq',
        'email_btq',
        'tel_btq',
        'password_btq',
        'solde_tdl',
        'device_token',
    ];

    public function articles(){
        return $this->hasMany(Article::class, 'id_btq');
    }

    public function commandes(){
        return $this->hasMany(Commande::class, 'id_btq');
    }

        public function deviceTokens()
    {
        return $this->hasMany(Notification::class);
    }



    protected function casts(): array
    {
        return [
            'password_btq' => 'hashed',
        ];
    }
}
