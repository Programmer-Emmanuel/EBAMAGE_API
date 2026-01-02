<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ResetPasswordAdminMail extends Mailable
{
    public $otp;
    public $admin;

    public function __construct($admin, $otp)
    {
        $this->admin = $admin;
        $this->otp = $otp;
    }

    public function build()
    {
        return $this->subject('Réinitialisation du mot de passe - EBAMAGE')
            ->view('emails.reset_password_admin');
    }
}