<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends TemplateMail
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param string $otpCode The OTP code to send
     * @param string $email The recipient's email address
     */
    public function __construct(
        public string $otpCode,
        public string $email
    )
    {
        // Find the OTP Email template
        $template = EmailTemplate::where('name', 'OTP Email')->firstOrFail();

        // Prepare data for the template
        $templateData = [
            'otpCode' => $this->otpCode,
            'email' => $this->email,
        ];

        // Call parent constructor with template and data
        parent::__construct($template, $templateData);
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
