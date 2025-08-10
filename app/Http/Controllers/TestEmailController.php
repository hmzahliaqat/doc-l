<?php

namespace App\Http\Controllers;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * This controller is for testing the email templates system.
 * It provides endpoints to test sending emails using the converted templates.
 */
class TestEmailController extends Controller
{
    /**
     * Show a form to test sending emails using the converted templates.
     *
     * @return \Illuminate\View\View
     */
    public function showForm()
    {
        $templates = EmailTemplate::where('is_active', true)->get();

        return view('test-email-form', [
            'templates' => $templates
        ]);
    }

    /**
     * Send a test email using the specified template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTestEmail(Request $request)
    {
        $request->validate([
            'template_id' => 'required|exists:email_templates,id',
            'to_email' => 'required|email',
            'variables' => 'nullable|array',
        ]);

        $template = EmailTemplate::findOrFail($request->template_id);
        $toEmail = $request->to_email;
        $variables = $request->variables ?? [];

        try {
            // Send the email using the TemplateMail class
            Mail::to($toEmail)->send(new TemplateMail($template, $variables));

            return response()->json([
                'success' => true,
                'message' => "Test email sent successfully to {$toEmail} using the '{$template->name}' template."
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Failed to send test email: {$e->getMessage()}"
            ], 500);
        }
    }

    /**
     * Test sending all converted templates to a specified email.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function testAllTemplates(Request $request)
    {
        $request->validate([
            'to_email' => 'required|email',
        ]);

        $toEmail = $request->to_email;
        $templates = EmailTemplate::where('is_active', true)->get();
        $results = [];

        foreach ($templates as $template) {
            try {
                // Prepare test data based on the template name
                $variables = $this->getTestDataForTemplate($template);

                // Send the email using the TemplateMail class
                Mail::to($toEmail)->send(new TemplateMail($template, $variables));

                $results[$template->name] = [
                    'success' => true,
                    'message' => "Email sent successfully."
                ];
            } catch (\Exception $e) {
                $results[$template->name] = [
                    'success' => false,
                    'message' => "Failed to send: {$e->getMessage()}"
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }

    /**
     * Get test data for a specific template.
     *
     * @param  \App\Models\EmailTemplate  $template
     * @return array
     */
    protected function getTestDataForTemplate(EmailTemplate $template)
    {
        // Default test data
        $testData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'company' => 'ACME Inc.',
            'login_url' => url('/login'),
        ];

        // Add specific test data based on template name
        switch ($template->name) {
            case 'OTP Email':
                $testData['otpCode'] = '123456';
                break;

            case 'Share Document':
                $testData['shared_document_id'] = '123';
                $testData['document_pdf_id'] = '456';
                $testData['employee_id'] = '789';
                $testData['type'] = 'reminder';
                break;

            case 'Email Verification':
                $testData['verificationUrl'] = url('/verify-email?token=test-token');
                $testData['user'] = (object)[
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com'
                ];
                break;
        }

        return $testData;
    }
}
