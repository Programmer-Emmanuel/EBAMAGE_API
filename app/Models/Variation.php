<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Variation extends Model
{
    
    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];
    protected $hidden = [
        'id',
        'id_btq',
        'pivot'
    ];

    protected $fillable = [
        'nom_variation',
        'lib_variation',
        'id_btq'
    ];

    protected $casts = [
        'lib_variation' => 'array',
    ];

    public function articles(){
        return $this->belongsToMany(Article::class, 'article_variations', 'id_variation', 'id_article');
    }


    public function boutique(){
        return $this->belongsTo(Boutique::class, 'id_btq');
    }
}
