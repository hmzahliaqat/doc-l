<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin']);

        // Create a super admin user
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);
    }

    public function test_preview_with_user_variable()
    {
        $this->actingAs($this->superAdmin);

        // Create a test template that uses the $user variable
        $template = EmailTemplate::create([
            'name' => 'Email Verification Test',
            'subject' => 'Verify Your Email Address',
            'template_type' => 'blade',
            'blade_content' => '
                <p>Hello {{ $user->name }},</p>
                <p>Please verify your email: {{ $verificationUrl }}</p>
            ',
            'is_active' => true,
        ]);

        // Register the variables for this template
        $template->variables()->create([
            'variable_name' => 'user',
            'display_name' => 'User',
        ]);

        $template->variables()->create([
            'variable_name' => 'verificationUrl',
            'display_name' => 'Verification URL',
        ]);

        // Test preview without providing user data
        $response = $this->postJson("/api/email-templates/{$template->id}/preview", [
            'data' => []
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['preview']);

        // The preview should contain the mock user name
        $this->assertStringContainsString('Hello John Doe', $response->json('preview'));

        // Test preview with custom user data
        $response = $this->postJson("/api/email-templates/{$template->id}/preview", [
            'data' => [
                'user' => [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com'
                ]
            ]
        ]);

        $response->assertStatus(200);

        // The preview should contain the custom user name
        $this->assertStringContainsString('Hello Jane Smith', $response->json('preview'));
    }
}
