<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TemplateMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The email template instance.
     *
     * @var \App\Models\EmailTemplate
     */
    protected $template;

    /**
     * The template data for variable substitution.
     *
     * @var array
     */
    protected $templateData;

    /**
     * Create a new message instance.
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $templateData
     * @return void
     */
    public function __construct(EmailTemplate $template, array $templateData = [])
    {
        $this->template = $template;
        $this->templateData = $templateData;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        $subject = $this->parseVariables($this->template->subject, $this->templateData);

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        $emailTemplateService = app(EmailTemplateService::class);

        $htmlContent = $emailTemplateService->renderTemplate($this->template, $this->templateData);
        $textContent = $emailTemplateService->renderTextTemplate($this->template, $this->templateData);

        return new Content(
            view: 'emails.template',
            text: 'emails.template-text',
            with: [
                'content' => $htmlContent,
                'text_content' => $textContent,
            ],
        );
    }

    /**
     * Parse variables in a string.
     *
     * @param string $content
     * @param array $data
     * @return string
     */
    protected function parseVariables($content, array $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }

        return $content;
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
