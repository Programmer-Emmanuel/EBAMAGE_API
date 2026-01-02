<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordClientMail extends Mailable
{
    public $otp;
    public $client;

    public function __construct($client, $otp)
    {
        $this->client = $client;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Réinitialisation du mot de passe - EBAMAGE')
            ->view('emails.reset_password_client');
    }
}
