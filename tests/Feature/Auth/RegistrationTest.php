<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        // User should not be authenticated after registration
        $this->assertGuest();

        // Check that the response contains the expected JSON data
        $response->assertStatus(201)
                 ->assertJson([
                     'message' => 'Registration successful! Please check your email to verify your account and then login.',
                     'email_verification_sent' => true,
                     'redirect_to' => 'login'
                 ]);
    }
}
