<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Portefeuille extends Model
{

    protected $fillable = [
        'montant',
        'role',
        'statut',
        'date_paiement',
        'id_commande',
        'id_beneficiaire',
        'is_paid'
    ];
    protected $appends = ['hashid'];
    protected $hidden = ['id'];

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }
    public function commande()
{
    return $this->belongsTo(Commande::class, 'id_commande');
}

public function boutique()
{
    return $this->belongsTo(Boutique::class, 'id_beneficiaire');
}

}
