<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeCreeeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $commande;
    public $role;

    public function __construct(Commande $commande, $role)
    {
        $this->commande = $commande;
        $this->role = $role;
    }

    public function build()
    {
        return $this->subject("Nouvelle commande - {$this->commande->code_commande}")
            ->view('emails.commande_creee')
            ->with([
                'commande' => $this->commande,
                'role' => $this->role,
            ]);
    }
}
