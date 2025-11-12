<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Vinkla\Hashids\Facades\Hashids;

class Solde extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'id_admin',
        'id_btq',
        'montant',
    ];

    protected $appends = ['hashid'];
    protected $hidden = ['id'];

    public function getHashidAttribute()
    {
        return Hashids::encode($this->id);
    }

    public function boutique()
    {
        return $this->belongsTo(Boutique::class, 'id_btq');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'id_admin');
    }
}
