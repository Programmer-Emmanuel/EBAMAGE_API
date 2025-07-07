<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Categorie extends Model
{

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];
    protected $hidden = [
        'id',
        'pivot'
    ];


    protected $fillable = [
        'nom_categorie',
        'image_categorie'
    ];




    public function articles(){
        return $this->belongsToMany(Article::class, 'corresponds', 'id_categorie', 'id_article');
    }
}
