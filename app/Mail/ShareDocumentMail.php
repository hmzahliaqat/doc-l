<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareDocumentMail extends TemplateMail
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct($shared_document_id, $document_pdf_id, $employee_id, $type)
    {
        // Find the Share Document template
        $template = EmailTemplate::where('name', 'Share Document')->firstOrFail();

        // Prepare data for the template
        $templateData = [
            'shared_document_id' => $shared_document_id,
            'document_pdf_id' => $document_pdf_id,
            'employee_id' => $employee_id,
            'type' => $type,
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
