<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CommandeLivreeMail extends Mailable
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
        return $this->subject("Commande livrée - {$this->commande->code_commande}")
            ->view('emails.commande_livree')
            ->with([
                'commande' => $this->commande,
                'role' => $this->role,
            ]);
    }
}
