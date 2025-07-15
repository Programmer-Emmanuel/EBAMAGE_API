<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Panier extends Model
{


    public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];

    protected $casts = [
        'variations' => 'array',
    ];


     protected $fillable = [
        'id_clt',
        'id_article',
        'quantite',
        'prix_total',
        'variations'
    ];

    // Relation avec l'utilisateur
    public function client()
    {
        return $this->belongsTo(User::class, 'id_clt');
    }

    // Relation avec l'article
    public function article()
    {
        return $this->belongsTo(Article::class, 'id_article');
    }
}
