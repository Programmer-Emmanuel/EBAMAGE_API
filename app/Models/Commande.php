<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class Commande extends Model
{
    // Si tu veux autoriser le remplissage en masse
    protected $fillable = [
        'id_clt',
        'id_btq',
        'id_article',
        'id_commune',
        'statut',
        'quartier',
    ];

    protected $hidden = [
        'id'
    ];

     public function getHashidAttribute(){
        return Hashids::encode($this->id);
    }
    protected $appends = ['hashid'];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_clt');
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class, 'id_btq');
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'id_article');
    }

    public function commune(): BelongsTo
    {
        return $this->belongsTo(Commune::class, 'id_commune');
    }
}
