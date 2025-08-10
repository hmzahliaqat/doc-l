<?php

namespace App\Services;

use App\Models\EmailTemplate;
use Html2Text\Html2Text;

class EmailTemplateService
{
    /**
     * Generate plain text version of HTML content
     *
     * @param string $htmlContent
     * @return string
     */
    public function generateTextVersion($htmlContent)
    {
        // Using Html2Text package for better conversion
        $html2Text = new Html2Text($htmlContent);
        return $html2Text->getText();
    }

    /**
     * Save a new email template
     *
     * @param array $data
     * @return \App\Models\EmailTemplate
     */
    public function saveTemplate($data)
    {
        return EmailTemplate::create([
            'name' => $data['name'],
            'subject' => $data['subject'],
            'blade_content' => $data['blade_content'] ?? $data['html_content'] ?? '',
            'is_active' => $data['is_active'] ?? true,
            'template_type' => $data['template_type'] ?? 'html',
        ]);
    }

    /**
     * Update an existing email template
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $data
     * @return \App\Models\EmailTemplate
     */
    public function updateTemplate(EmailTemplate $template, $data)
    {
        $updateData = [];

        if (isset($data['name'])) {
            $updateData['name'] = $data['name'];
        }

        if (isset($data['subject'])) {
            $updateData['subject'] = $data['subject'];
        }

        if (isset($data['is_active'])) {
            $updateData['is_active'] = $data['is_active'];
        }

        if (isset($data['template_type'])) {
            $updateData['template_type'] = $data['template_type'];
        }

        // Handle content field - prioritize blade_content, fall back to html_content
        if (array_key_exists('blade_content', $data)) {
            $updateData['blade_content'] = $data['blade_content'];
        } elseif (array_key_exists('html_content', $data)) {
            $updateData['blade_content'] = $data['html_content'];
        }

        $template->update($updateData);

        return $template;
    }

    /**
     * Render a template with variable substitution
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $data
     * @return string
     */
    public function renderTemplate(EmailTemplate $template, array $data = [])
    {
        // Handle different template types
        if ($template->template_type === 'blade' || $template->template_type === 'mail-component') {
            return $this->renderBladeTemplate($template, $data);
        } else {
            // Default to simple variable substitution for HTML templates
            $content = $template->blade_content;

            foreach ($data as $key => $value) {
                $content = str_replace('{{' . $key . '}}', $value, $content);
            }

            return $content;
        }
    }

    /**
     * Render a Blade template with variable substitution
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $data
     * @return string
     */
    protected function renderBladeTemplate(EmailTemplate $template, array $data = [])
    {
        // Create a temporary view file with the blade content
        $tempViewPath = storage_path('framework/views/' . md5($template->id . $template->updated_at) . '.blade.php');
        file_put_contents($tempViewPath, $template->blade_content);

        // Render the view with the provided data
        $content = view()->file($tempViewPath, $data)->render();

        // Clean up the temporary file
        if (file_exists($tempViewPath)) {
            unlink($tempViewPath);
        }

        return $content;
    }


    /**
     * Render a template's text version with variable substitution
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $data
     * @return string
     */
    public function renderTextTemplate(EmailTemplate $template, array $data = [])
    {
        // Always generate text from rendered HTML content
        $htmlContent = $this->renderTemplate($template, $data);
        return $this->generateTextVersion($htmlContent);
    }
}
