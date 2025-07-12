<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;
class Article extends Model
{

    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];
    protected $hidden = [
        'id',
        'id_btq'
    ];

    protected $fillable = [
        'nom_article',
        'prix',
        'old_price',
        'images',
        'description',
        'id_btq'
    ];


    public function boutique(){
        return $this->belongsTo(Boutique::class, 'id_btq');
    }

    public function categories(){
        return $this->belongsToMany(Categorie::class, 'corresponds', 'id_article', 'id_categorie');
    }

    public function variations(){
        return $this->belongsToMany(Variation::class, 'article_variations', 'id_article', 'id_variation');
    }

    public function commandes(){
        return $this->hasMany(Commande::class, 'id_article');
    }


}
