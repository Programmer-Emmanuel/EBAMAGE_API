<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordBoutiqueMail extends Mailable
{
    public $otp;
    public $boutique;

    public function __construct($boutique, $otp)
    {
        $this->boutique = $boutique;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Réinitialisation du mot de passe - EBAMAGE')
            ->view('emails.reset_password_boutique');
    }
}
