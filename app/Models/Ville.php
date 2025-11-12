<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Ville extends Model
{
    protected $fillable = [
        'lib_ville'
    ];

    protected $hidden = [
        'id'
    ];

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];

    public function communes(){
        return $this->hasMany(Commune::class, 'id_ville');
    }

}
