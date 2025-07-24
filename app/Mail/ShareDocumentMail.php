<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ShareDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $shared_document_id;
    public $document_pdf_id;
    public $employee_id;
    public $type;
    /**
     * Create a new message instance.
     */
    public function __construct($shared_document_id, $document_pdf_id, $employee_id, $type)
    {
        $this->shared_document_id = $shared_document_id;
        $this->document_pdf_id = $document_pdf_id;
        $this->employee_id = $employee_id;
        $this->type = $type;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Document Mail',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.share-document',
            with:[
                'shared_document_id' => $this->shared_document_id,
                'document_pdf_id' => $this->document_pdf_id,
                'employee_id' => $this->employee_id,
                'type' => $this->type,
            ],
        );
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
