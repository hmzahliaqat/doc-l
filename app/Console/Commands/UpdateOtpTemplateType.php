<?php

namespace App\Console\Commands;

use App\Models\EmailTemplate;
use Illuminate\Console\Command;

class UpdateOtpTemplateType extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:update-otp-template-type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update the OTP Email template type from mail-component to blade';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating OTP Email template type...');

        // Find the OTP Email template
        $template = EmailTemplate::where('name', 'OTP Email')->first();

        if (!$template) {
            $this->error('OTP Email template not found');
            return 1;
        }

        $this->info('Found OTP Email template with ID: ' . $template->id);

        // Update the template type
        $template->template_type = 'blade';
        $template->save();

        $this->info('Successfully updated OTP Email template type from mail-component to blade');

        return 0;
    }
}
