<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Vinkla\Hashids\Facades\Hashids;

class Admin extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $fillable = [
        'nom',
        'email',
        'tel',
        'password',
        'solde_admin',
    ];

        public function getHashidAttribute(){
            return Hashids::encode($this->id);
        }
        protected $appends = ['hashid'];
        protected $hidden = ['id'];
}
