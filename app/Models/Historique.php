<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Historique extends Model
{

    protected $fillable = [
        'lib_recherche'
    ];

    protected $hidden = [
        'id'
    ];
    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
}
