<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'id_admin',
        'id_btq',
        'id_clt',
        'id_livreur',
        'id_commande',
        'reference',
        'code_paiement',
        'provider',
        'channel',
        'montant',
        'commission_admin',
        'montant_net',
        'statut',
        'date_transaction',
    ];

    protected $appends = ['hashid'];
    protected $hidden = ['id'];

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function boutique() { return $this->belongsTo(Boutique::class, 'id_btq'); }
    public function admin() { return $this->belongsTo(Admin::class, 'id_admin'); }
    public function user() { return $this->belongsTo(User::class, 'id_clt'); }
    public function commande() { return $this->belongsTo(Commande::class, 'id_commande'); }
}
