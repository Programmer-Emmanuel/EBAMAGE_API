<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeAnnuleeMail extends Mailable
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
        return $this->subject("Commande annulée - {$this->commande->code_commande}")
            ->view('emails.commande_annulee')
            ->with([
                'commande' => $this->commande,
                'role' => $this->role,
            ]);
    }
}
