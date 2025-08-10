<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class EmailTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define the templates to convert
        $templates = [
            [
                'file' => 'otp.blade.php',
                'name' => 'OTP Email',
                'subject' => 'Your One-Time Password (OTP)',
                'type' => 'mail-component',
                'variables' => ['otpCode']
            ],
            [
                'file' => 'share-document.blade.php',
                'name' => 'Share Document',
                'subject' => 'Document Shared with You',
                'type' => 'blade',
                'variables' => ['type', 'shared_document_id', 'document_pdf_id', 'employee_id']
            ],
            [
                'file' => 'verify-email.blade.php',
                'name' => 'Email Verification',
                'subject' => 'Verify Your Email Address',
                'type' => 'blade',
                'variables' => ['user', 'verificationUrl']
            ]
        ];

        // Process each template
        foreach ($templates as $template) {
            $this->convertTemplate($template);
        }

        $this->command->info('Email templates converted successfully!');
    }

    /**
     * Convert a template to the new system
     *
     * @param array $template
     * @return void
     */
    protected function convertTemplate(array $template): void
    {
        $filePath = resource_path('views/emails/' . $template['file']);

        // Check if the template file exists
        if (!File::exists($filePath)) {
            $this->command->error("Template file not found: {$filePath}");
            return;
        }

        // Read the template content
        $content = File::get($filePath);

        // Create or update the template in the database
        $emailTemplate = EmailTemplate::updateOrCreate(
            ['name' => $template['name']],
            [
                'subject' => $template['subject'],
                'template_type' => $template['type'],
                'blade_content' => $content,
                'is_active' => true
            ]
        );

        // Register variables for the template
        $this->registerVariables($emailTemplate, $template['variables']);

        $this->command->info("Converted template: {$template['name']}");
    }

    /**
     * Register variables for a template
     *
     * @param \App\Models\EmailTemplate $template
     * @param array $variables
     * @return void
     */
    protected function registerVariables(EmailTemplate $template, array $variables): void
    {
        // Clear existing variables
        $template->variables()->delete();

        // Add new variables
        foreach ($variables as $variable) {
            $displayName = Str::title(Str::snake($variable, ' '));

            $template->variables()->create([
                'variable_name' => $variable,
                'display_name' => $displayName,
                'default_value' => null
            ]);
        }
    }
}
