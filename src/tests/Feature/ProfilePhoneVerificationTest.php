<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProfilePhoneVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_updating_profile_with_new_phone_sends_otp(): void
    {
        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $response = $this->actingAs($user)->put('/profile/update', [
            'name'  => 'Sidiq Updated',
            'email' => $user->email,
            'phone' => '082222222222',
        ]);

        $response->assertRedirect('/profile/verify-phone');
        $this->assertEquals('082222222222', session('pending_phone'));
        $this->assertNotNull(session('phone_otp'));

        Http::assertSent(function ($request) {
            return str_contains($request['message'], 'Kode verifikasi');
        });

        // User's phone should NOT be updated yet
        $this->assertEquals('081111111111', $user->fresh()->phone);
    }

    public function test_submitting_correct_otp_verifies_and_saves_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'pending_phone'     => '082222222222',
                'phone_otp'         => '1234',
                'phone_otp_expires' => now()->addMinutes(10),
            ])
            ->post('/profile/verify-phone', [
                'otp' => '1234',
            ]);

        $response->assertRedirect('/profile');
        $this->assertEquals('082222222222', $user->fresh()->phone);
        $this->assertNull(session('pending_phone'));
        $this->assertNull(session('phone_otp'));
    }

    public function test_submitting_incorrect_otp_fails(): void
    {
        $user = User::factory()->create([
            'phone' => '081111111111',
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'pending_phone'     => '082222222222',
                'phone_otp'         => '1234',
                'phone_otp_expires' => now()->addMinutes(10),
            ])
            ->from('/profile/verify-phone')
            ->post('/profile/verify-phone', [
                'otp' => '9999',
            ]);

        $response->assertRedirect('/profile/verify-phone');
        $this->assertEquals('081111111111', $user->fresh()->phone);
    }
}
