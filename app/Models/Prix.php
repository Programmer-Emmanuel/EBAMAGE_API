<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Prix extends Model
{

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }

    protected $fillable = [
        'prix'
    ];
}
