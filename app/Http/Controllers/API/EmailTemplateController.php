<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use Illuminate\Http\Request;

class EmailTemplateController extends Controller
{
    protected $templateService;

    public function __construct(EmailTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Display a listing of the email templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return EmailTemplate::all();
    }

    /**
     * Display the specified email template.
     *
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function show(EmailTemplate $emailTemplate)
    {
        return $emailTemplate;
    }

    /**
     * Store a newly created email template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'subject' => 'required|string|max:255',
            'blade_content' => 'nullable',
            'template_type' => 'string|in:html,blade,mail-component',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->saveTemplate($validated);

        return response()->json($template, 201);
    }

    /**
     * Update the specified email template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'subject' => 'string|max:255',
            'blade_content' => 'nullable|string',
            'template_type' => 'string|in:html,blade,mail-component',
            'is_active' => 'boolean',
        ]);

        $template = $this->templateService->updateTemplate($emailTemplate, $validated);

        return response()->json($template);
    }

    /**
     * Remove the specified email template from storage.
     *
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return response()->json(null, 204);
    }

    /**
     * Preview the specified email template with variable substitution.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EmailTemplate  $emailTemplate
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $data = $request->input('data', []);

        // Get registered variables for this template
        $registeredVariables = $emailTemplate->variables()->pluck('variable_name')->toArray();

        // Provide mock data for registered variables that aren't in the request data
        foreach ($registeredVariables as $variable) {
            if (!array_key_exists($variable, $data)) {
                $data[$variable] = $this->getMockDataForVariable($variable);
            } else {
                // Convert array data to objects for complex variables
                $data[$variable] = $this->convertToObjectIfNeeded($variable, $data[$variable]);
            }
        }

        $htmlContent = $this->templateService->renderTemplate($emailTemplate, $data);

        return response()->json(['preview' => $htmlContent]);
    }

    /**
     * Convert array data to objects for complex variables if needed.
     *
     * @param  string  $variable
     * @param  mixed  $value
     * @return mixed
     */
    protected function convertToObjectIfNeeded($variable, $value)
    {
        // If the value is already an object or not an array, return it as is
        if (!is_array($value)) {
            return $value;
        }

        // For complex variables that are expected to be objects
        switch ($variable) {
            case 'user':
                // Convert user array to object
                return (object) $value;

            default:
                // For other variables, return as is
                return $value;
        }
    }

    /**
     * Get mock data for a template variable.
     *
     * @param  string  $variable
     * @return mixed
     */
    protected function getMockDataForVariable($variable)
    {
        switch ($variable) {
            case 'user':
                // Create a mock user object with common properties
                return (object)[
                    'name' => 'John Doe',
                    'email' => 'john.doe@example.com',
                    'id' => 1
                ];

            case 'verificationUrl':
                return url('/verify-email?token=mock-verification-token');

            case 'otpCode':
                return '123456';

            case 'type':
                return 'reminder';

            case 'shared_document_id':
                return '123';

            case 'document_pdf_id':
                return '456';

            case 'employee_id':
                return '789';

            default:
                // For any other variables, return a placeholder string
                return "{{$variable}}";
        }
    }
}
