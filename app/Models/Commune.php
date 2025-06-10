<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Commune extends Model
{
     protected $fillable = ['lib_commune', 'id_ville'];

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];
    protected $hidden = [
        'id',
        'id_ville'
    ];

    public function ville(){
        return $this->belongsTo(Ville::class, 'id_ville');
    }
}
