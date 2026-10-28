<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginThrottlingTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_login_with_correct_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'user@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_failed_login_shows_error(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email' => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->create([
            'email' => 'target@example.com',
            'password' => bcrypt('correct-password'),
        ]);

        // 5 failed attempts
        for ($i = 0; $i < 5; $i++) {
            $response = $this->from('/login')->post('/login', [
                'email' => 'target@example.com',
                'password' => 'wrong-pass',
            ]);
            $response->assertRedirect('/login');
            $response->assertSessionHasErrors(['email' => 'Email atau password salah.']);
        }

        // 6th attempt should be throttled
        $response = $this->from('/login')->post('/login', [
            'email' => 'target@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');

        $errorMessage = session('errors')->first('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', $errorMessage);
        $this->assertStringContainsString('detik', $errorMessage);
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $user = User::factory()->create([
            'email' => 'clean@example.com',
            'password' => bcrypt('secret123'),
        ]);

        // 2 failed attempts
        for ($i = 0; $i < 2; $i++) {
            $this->post('/login', [
                'email' => 'clean@example.com',
                'password' => 'badpass',
            ]);
        }

        // 3rd attempt is successful
        $response = $this->post('/login', [
            'email' => 'clean@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);

        // Check RateLimiter attempts is 0
        $throttleKey = 'clean@example.com|127.0.0.1';
        $this->assertEquals(0, RateLimiter::attempts($throttleKey));
    }
}
