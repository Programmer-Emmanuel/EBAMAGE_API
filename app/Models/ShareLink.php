<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class ShareLink extends Model
{
    protected $fillable = [
        'link_shop',
        'link_article'
    ];

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
}
