<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Console\Command;

class TestMailComponentRendering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:mail-component';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test rendering of mail components in email templates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing mail component rendering...');

        // Find the OTP Email template (which uses mail components)
        $template = EmailTemplate::where('name', 'OTP Email')->first();

        if (!$template) {
            $this->error('OTP Email template not found');
            return 1;
        }

        $this->info('Found OTP Email template with ID: ' . $template->id);

        try {
            // Prepare test data
            $data = [
                'otpCode' => '123456'
            ];

            $this->info('Rendering template with test data...');

            // Get the email template service
            $emailTemplateService = app(EmailTemplateService::class);

            // Render the template
            $renderedHtml = $emailTemplateService->renderTemplate($template, $data);

            // Check if the rendered HTML contains expected content
            if (strpos($renderedHtml, '123456') !== false) {
                $this->info('Mail component rendered successfully!');
                $this->info('The rendered HTML contains the OTP code: 123456');

                // Check if the rendered HTML contains mail component elements
                if (strpos($renderedHtml, 'panel') !== false) {
                    $this->info('The rendered HTML contains mail component elements');
                } else {
                    $this->warn('The rendered HTML does not contain mail component elements');
                }

                return 0;
            } else {
                $this->error('Mail component rendered, but the OTP code was not found in the output');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Error rendering mail component: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
