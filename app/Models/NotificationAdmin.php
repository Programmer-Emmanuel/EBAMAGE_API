<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationAdmin extends Model
{
    protected $fillable = [
        'admin_id',
        'title',
        'message',
        'type'
    ];

    // Relation avec le client
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
