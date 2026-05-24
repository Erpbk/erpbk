<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompanyOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $companyName,
        public string $purpose = 'registration'
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->purpose === 'email_change'
            ? __('Your verification code to change company email')
            : __('Your verification code for company registration');

        return new Envelope(
            subject: $subject,
            from: config('mail.from.address'),
            replyTo: config('mail.from.address'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.company-otp'
        );
    }
}
