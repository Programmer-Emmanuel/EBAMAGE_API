<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Publicite extends Model
{

    protected $fillable = [
        "images",
        "role"
    ];

    public function getHashidAttribute()
{
    return Hashids::encode($this->id);
}
}
