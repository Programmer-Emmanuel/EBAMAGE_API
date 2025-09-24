<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $fillable = [
        'user_id',
        'boutique_id',
        'title',
        'message',
    ];

    // Relation avec le client
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relation avec la boutique
    public function boutique()
    {
        return $this->belongsTo(Boutique::class);
    }
}
