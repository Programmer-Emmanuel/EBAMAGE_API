<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Vinkla\Hashids\Facades\Hashids;

class Commande extends Model
{
protected $fillable = [
    'id_clt',
    'id_btq',
    'id_panier', // à garder si tu l'utilises
    'id_ville',
    'id_commune',          // important si tu l'as dans ta table
    'articles',            // <-- nouveau champ JSON qui stocke tous les articles + variations
    'quantite',
    'prix',
    'livraison',
    'prix_total',
    'statut',
    'quartier',
    'moyen_de_paiement', 
];

protected $casts = [
    'articles' => 'array',
];



    protected $hidden = ['id'];

    protected $appends = ['hashid', 'moyen_de_paiement_libelle'];

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }

    // Accessor pour le libellé du moyen de paiement
    public function getMoyenDePaiementLibelleAttribute()
    {
        return $this->moyen_de_paiement == 1 ? "à la livraison" : "TDLPay";
    }

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_clt');
    }

    public function boutique(): BelongsTo
    {
        return $this->belongsTo(Boutique::class, 'id_btq');
    }

    public function panier(): BelongsTo
    {
        return $this->belongsTo(Panier::class, 'id_panier');
    }

    public function ville(): BelongsTo
    {
        return $this->belongsTo(Ville::class, 'id_ville');
    }

    public function commune()
{
    return $this->belongsTo(Commune::class, 'id_commune');
}




}
