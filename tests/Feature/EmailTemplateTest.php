<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\User;
use App\Services\EmailTemplateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $emailTemplateService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin']);

        // Create a super admin user
        $this->superAdmin = User::factory()->create();
        $this->superAdmin->assignRole($superAdminRole);

        $this->emailTemplateService = app(EmailTemplateService::class);
    }

    public function test_super_admin_can_create_email_template()
    {
        $this->actingAs($this->superAdmin);

        $response = $this->postJson('/api/email-templates', [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to our platform, {{name}}!',
            'html_content' => '<h1>Welcome, {{name}}!</h1><p>Thank you for joining our platform.</p>',
            'is_active' => true,
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'name' => 'Welcome Email',
                'subject' => 'Welcome to our platform, {{name}}!',
            ]);

        $this->assertDatabaseHas('email_templates', [
            'name' => 'Welcome Email',
            'subject' => 'Welcome to our platform, {{name}}!',
        ]);
    }

    public function test_super_admin_can_view_email_templates()
    {
        $this->actingAs($this->superAdmin);

        // Create a test template
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'text_content' => 'Test content',
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/email-templates');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Test Template',
                'subject' => 'Test Subject',
            ]);
    }

    public function test_super_admin_can_update_email_template()
    {
        $this->actingAs($this->superAdmin);

        // Create a test template
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'text_content' => 'Test content',
            'is_active' => true,
        ]);

        $response = $this->putJson("/api/email-templates/{$template->id}", [
            'name' => 'Updated Template',
            'subject' => 'Updated Subject',
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'name' => 'Updated Template',
                'subject' => 'Updated Subject',
            ]);

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Template',
            'subject' => 'Updated Subject',
        ]);
    }

    public function test_super_admin_can_delete_email_template()
    {
        $this->actingAs($this->superAdmin);

        // Create a test template
        $template = EmailTemplate::create([
            'name' => 'Test Template',
            'subject' => 'Test Subject',
            'html_content' => '<p>Test content</p>',
            'text_content' => 'Test content',
            'is_active' => true,
        ]);

        $response = $this->deleteJson("/api/email-templates/{$template->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('email_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_super_admin_can_preview_email_template()
    {
        $this->actingAs($this->superAdmin);

        // Create a test template
        $template = EmailTemplate::create([
            'name' => 'Welcome Email',
            'subject' => 'Welcome, {{name}}!',
            'html_content' => '<h1>Welcome, {{name}}!</h1><p>Thank you for joining our platform, {{name}}.</p>',
            'text_content' => 'Welcome, {{name}}! Thank you for joining our platform, {{name}}.',
            'is_active' => true,
        ]);

        $response = $this->postJson("/api/email-templates/{$template->id}/preview", [
            'data' => [
                'name' => 'John Doe',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment([
                'preview' => '<h1>Welcome, John Doe!</h1><p>Thank you for joining our platform, John Doe.</p>',
            ]);
    }

    public function test_email_template_service_can_render_template()
    {
        // Create a test template
        $template = EmailTemplate::create([
            'name' => 'Welcome Email',
            'subject' => 'Welcome, {{name}}!',
            'html_content' => '<h1>Welcome, {{name}}!</h1><p>Thank you for joining our platform, {{name}}.</p>',
            'text_content' => 'Welcome, {{name}}! Thank you for joining our platform, {{name}}.',
            'is_active' => true,
        ]);

        $data = ['name' => 'John Doe'];

        $htmlContent = $this->emailTemplateService->renderTemplate($template, $data);
        $textContent = $this->emailTemplateService->renderTextTemplate($template, $data);

        $this->assertEquals('<h1>Welcome, John Doe!</h1><p>Thank you for joining our platform, John Doe.</p>', $htmlContent);
        $this->assertEquals('Welcome, John Doe! Thank you for joining our platform, John Doe.', $textContent);
    }

    public function test_non_super_admin_cannot_access_email_templates()
    {
        // Create a regular user
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/email-templates');

        $response->assertStatus(403);
    }
}
