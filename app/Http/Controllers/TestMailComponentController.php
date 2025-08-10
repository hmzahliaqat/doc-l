<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;

class TestMailComponentController extends Controller
{
    /**
     * Test rendering a mail component template
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function testMailComponent(Request $request)
    {
        // Find the OTP Email template (which uses mail components)
        $template = EmailTemplate::where('name', 'OTP Email')->first();

        if (!$template) {
            return response()->json([
                'success' => false,
                'message' => 'OTP Email template not found'
            ], 404);
        }

        try {
            // Prepare test data
            $data = [
                'otpCode' => '123456'
            ];

            // Get the email template service
            $emailTemplateService = app(EmailTemplateService::class);

            // Render the template
            $renderedHtml = $emailTemplateService->renderTemplate($template, $data);

            return response()->json([
                'success' => true,
                'message' => 'Mail component rendered successfully',
                'html' => $renderedHtml
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rendering mail component: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }
}
