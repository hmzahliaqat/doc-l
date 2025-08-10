<?php

namespace App\Http\Controllers;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

/**
 * This is an example controller demonstrating how to use the email templates system.
 * This is not meant to be used in production, but rather as a reference for how to
 * integrate the email templates system into your application.
 */
class ExampleController extends Controller
{
    /**
     * Send a welcome email to a user using an email template.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendWelcomeEmail(Request $request)
    {
        // Find the welcome email template
        $template = EmailTemplate::where('name', 'Welcome Email')->first();

        if (!$template) {
            return response()->json(['message' => 'Welcome email template not found'], 404);
        }

        // Get the user from the request
        $user = $request->user();

        // Prepare data for the template
        $data = [
            'name' => $user->name,
            'company' => 'Your Company',
            'login_url' => url('/login'),
        ];

        // Send the email
        Mail::to($user->email)
            ->send(new TemplateMail($template, $data));

        return response()->json(['message' => 'Welcome email sent successfully']);
    }

    /**
     * Send a document notification email to a user using an email template.
     *
     * @param Request $request
     * @param int $documentId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendDocumentNotification(Request $request, $documentId)
    {
        // Find the document notification email template
        $template = EmailTemplate::where('name', 'Document Notification')->first();

        if (!$template) {
            return response()->json(['message' => 'Document notification template not found'], 404);
        }

        // Get the recipient email from the request
        $recipientEmail = $request->input('email');

        if (!$recipientEmail) {
            return response()->json(['message' => 'Recipient email is required'], 400);
        }

        // Prepare data for the template
        $data = [
            'document_name' => $request->input('document_name', 'Document'),
            'document_url' => url("/documents/{$documentId}"),
            'sender_name' => $request->user()->name,
        ];

        // Send the email
        Mail::to($recipientEmail)
            ->send(new TemplateMail($template, $data));

        return response()->json(['message' => 'Document notification email sent successfully']);
    }

    /**
     * Create default email templates for the application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function createDefaultTemplates()
    {
        // Create a welcome email template
        $welcomeTemplate = EmailTemplate::updateOrCreate(
            ['name' => 'Welcome Email'],
            [
                'subject' => 'Welcome to {{company}}, {{name}}!',
                'html_content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                        <h1 style="color: #333;">Welcome to {{company}}!</h1>
                    </div>
                    <div style="padding: 20px;">
                        <p>Hello {{name}},</p>
                        <p>Thank you for joining {{company}}. We\'re excited to have you on board!</p>
                        <p>You can log in to your account using the link below:</p>
                        <p style="text-align: center;">
                            <a href="{{login_url}}" style="display: inline-block; background-color: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Log In</a>
                        </p>
                        <p>If you have any questions, please don\'t hesitate to contact our support team.</p>
                        <p>Best regards,<br>The {{company}} Team</p>
                    </div>
                    <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                        <p>&copy; ' . date('Y') . ' {{company}}. All rights reserved.</p>
                    </div>
                </div>
                ',
                'is_active' => true,
            ]
        );

        // Create a document notification email template
        $documentTemplate = EmailTemplate::updateOrCreate(
            ['name' => 'Document Notification'],
            [
                'subject' => '{{sender_name}} has shared a document with you',
                'html_content' => '
                <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">
                    <div style="background-color: #f8f9fa; padding: 20px; text-align: center;">
                        <h1 style="color: #333;">Document Shared</h1>
                    </div>
                    <div style="padding: 20px;">
                        <p>Hello,</p>
                        <p>{{sender_name}} has shared a document with you: <strong>{{document_name}}</strong></p>
                        <p>You can view the document using the link below:</p>
                        <p style="text-align: center;">
                            <a href="{{document_url}}" style="display: inline-block; background-color: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">View Document</a>
                        </p>
                        <p>If you have any questions, please contact {{sender_name}} directly.</p>
                        <p>Best regards,<br>The Team</p>
                    </div>
                    <div style="background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666;">
                        <p>&copy; ' . date('Y') . ' All rights reserved.</p>
                    </div>
                </div>
                ',
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => 'Default email templates created successfully',
            'templates' => [
                'welcome' => $welcomeTemplate,
                'document' => $documentTemplate,
            ],
        ]);
    }
}
