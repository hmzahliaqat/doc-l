<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConvertEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:convert-template
                            {file : Path to the email template file (relative to resources/views/emails)}
                            {name : Name of the template}
                            {subject : Subject line for emails using this template}
                            {--type=blade : Type of template (html, blade, or mail-component)}
                            {--variables=* : Variables used in the template (comma-separated list)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert an email template file to the email templates system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the arguments and options
        $file = $this->argument('file');
        $name = $this->argument('name');
        $subject = $this->argument('subject');
        $type = $this->option('type');
        $variables = $this->option('variables');

        // If variables is a string (comma-separated list), convert it to an array
        if (is_string($variables)) {
            $variables = explode(',', $variables);
        }

        // Validate the template type
        if (!in_array($type, ['html', 'blade', 'mail-component'])) {
            $this->error("Invalid template type: {$type}. Must be one of: html, blade, mail-component");
            return 1;
        }

        // Build the file path
        $filePath = resource_path('views/emails/' . $file);

        // Check if the file exists
        if (!File::exists($filePath)) {
            $this->error("Template file not found: {$filePath}");
            return 1;
        }

        // Read the template content
        $content = File::get($filePath);

        // Create or update the template in the database
        $emailTemplate = EmailTemplate::updateOrCreate(
            ['name' => $name],
            [
                'subject' => $subject,
                'template_type' => $type,
                'html_content' => 'Placeholder content - will be generated at runtime',
                'text_content' => 'Placeholder content - will be generated at runtime',
                'is_active' => true
            ]
        );

        // Set the appropriate content based on template type
        if ($type === 'html') {
            $emailTemplate->html_content = $content;
            $emailTemplate->blade_content = null;
        } else {
            $emailTemplate->blade_content = $content;
        }

        $emailTemplate->save();

        // Register variables for the template
        $this->registerVariables($emailTemplate, $variables);

        $this->info("Template '{$name}' converted successfully!");

        return 0;
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
            $variable = trim($variable);
            if (empty($variable)) {
                continue;
            }

            $displayName = Str::title(Str::snake($variable, ' '));

            $template->variables()->create([
                'variable_name' => $variable,
                'display_name' => $displayName,
                'default_value' => null
            ]);

            $this->info("Registered variable: {$variable}");
        }
    }
}
