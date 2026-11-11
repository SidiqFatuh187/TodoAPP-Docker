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
        Http::fake(['*' => Http::response(['status' => true])]);
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
        $this->assertEquals('6282222222222', session('pending_phone'));
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
                'phone_otp_user' => $user->id,
            ])
            ->post('/profile/verify-phone', [
                'otp' => '1234',
            ]);

        $response->assertRedirect('/profile');
        $this->assertEquals('6282222222222', $user->fresh()->phone);
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
                'phone_otp_user' => $user->id,
            ])
            ->from('/profile/verify-phone')
            ->post('/profile/verify-phone', [
                'otp' => '9999',
            ]);

        $response->assertRedirect('/profile/verify-phone');
        $this->assertEquals('081111111111', $user->fresh()->phone);
    }

    public function test_equivalent_legacy_phone_cannot_be_claimed(): void
    {
        $user = User::factory()->create(['phone' => '081111111111']);
        foreach (['082222222222', '6282222222222', '+6282222222222'] as $phone) {
            $owner = User::factory()->create(['phone' => $phone]);
            $this->actingAs($user)->from('/profile/edit')->put('/profile/update', [
                'name' => $user->name, 'email' => $user->email, 'phone' => '082222222222',
            ])->assertSessionHasErrors('phone');
            $owner->delete();
        }
        Http::assertNothingSent();
        $this->assertSame('081111111111', $user->fresh()->phone);
    }

    public function test_collision_is_rechecked_when_verification_completes(): void
    {
        $user = User::factory()->create(['phone' => '081111111111']);
        $this->actingAs($user)->put('/profile/update', [
            'name' => $user->name, 'email' => $user->email, 'phone' => '082222222222',
        ]);
        $otp = session('phone_otp');
        User::factory()->create(['phone' => '+6282222222222']);
        $this->post('/profile/verify-phone', ['otp' => $otp])->assertSessionHasErrors('phone');
        $this->assertSame('081111111111', $user->fresh()->phone);
    }

    public function test_update_and_resend_share_dispatch_cooldown(): void
    {
        $user = User::factory()->create(['phone' => '081111111111']);
        $input = ['name' => $user->name, 'email' => $user->email, 'phone' => '082222222222'];
        $this->actingAs($user)->put('/profile/update', $input);
        $this->post('/profile/resend-phone-otp')->assertSessionHasErrors('phone');
        $this->put('/profile/update', $input)->assertSessionHasErrors('phone');
        Http::assertSentCount(1);
    }

    public function test_phone_verification_locks_after_five_wrong_codes_even_after_resend(): void
    {
        $user = User::factory()->create(['phone' => '081111111111']);
        $this->actingAs($user)->withSession([
            'pending_phone' => '6282222222222', 'phone_otp' => '1234',
            'phone_otp_expires' => now()->addMinutes(10), 'phone_otp_user' => $user->id,
        ]);
        for ($i = 0; $i < 5; $i++) {
            $this->post('/profile/verify-phone', ['otp' => '9999']);
        }
        $this->post('/profile/resend-phone-otp');
        $this->post('/profile/verify-phone', ['otp' => session('phone_otp')])->assertSessionHas('error');
        $this->assertSame('081111111111', $user->fresh()->phone);
    }

    public function test_delivery_rejection_and_http_errors_do_not_create_pending_challenge(): void
    {
        $user = User::factory()->create(['phone' => '081111111111']);
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::fakeSequence()->push(['status' => false], 200)->push(['status' => false], 500);
        for ($i = 0; $i < 2; $i++) {
            $this->actingAs($user)->put('/profile/update', [
                'name' => $user->name, 'email' => $user->email, 'phone' => '082222222222',
            ])->assertSessionHas('error')->assertSessionMissing('info');
            $this->assertNull(session('pending_phone'));
            $this->assertSame('081111111111', $user->fresh()->phone);
            $this->travel(61)->seconds();
        }
        Http::assertSentCount(2);
    }

    public function test_pending_challenge_cannot_be_used_by_another_account(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['phone' => null]);
        $this->actingAs($other)->withSession([
            'pending_phone' => '6282222222222', 'phone_otp' => '1234',
            'phone_otp_expires' => now()->addMinutes(10), 'phone_otp_user' => $user->id,
        ])->post('/profile/verify-phone', ['otp' => '1234'])->assertRedirect('/profile/edit');
        $this->assertNull($other->fresh()->phone);
    }
}
