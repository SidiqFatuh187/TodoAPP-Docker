<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    private function startReset(User $user): string
    {
        $this->post('/password/forgot', ['email' => $user->email])->assertRedirect('/password/verify');
        return DB::table('password_reset_otps')->where('email', $user->email)->value('otp');
    }

    private function passwordInput(): array
    {
        return ['password' => 'new-password-123', 'password_confirmation' => 'new-password-123'];
    }

    public function test_starting_reset_for_another_email_clears_previous_verified_grant(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $oldPassword = $second->password;
        $otp = $this->startReset($first);
        $this->post('/password/verify', ['otp' => $otp])->assertRedirect('/password/reset');
        $this->assertNotNull(session('reset_grant'));
        $this->startReset($second);
        $this->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $second->fresh()->password);
    }

    public function test_verified_grant_cannot_be_retargeted_by_session_email(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $oldPassword = $second->password;
        $otp = $this->startReset($first);
        $this->post('/password/verify', ['otp' => $otp]);
        $this->withSession(['reset_email' => $second->email])
            ->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $second->fresh()->password);
    }

    public function test_grant_expiry_is_enforced_at_password_write_boundary(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp]);
        $this->travel(5)->minutes();
        $this->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_record_expiry_is_rechecked_even_when_session_grant_has_time_left(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp]);
        DB::table('password_reset_otps')->where('email', $user->email)->update(['expired_at' => now()->subSecond()]);
        $this->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_verified_reset_is_consumed_once_even_if_session_proof_is_replayed(): void
    {
        $user = User::factory()->create();
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp]);
        $proof = session()->only(['reset_email', 'reset_record', 'reset_challenge', 'reset_grant']);
        $this->post('/password/reset', $this->passwordInput())->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
        $this->withSession($proof)->post('/password/reset', [
            'password' => 'replayed-password', 'password_confirmation' => 'replayed-password',
        ])->assertRedirect('/password/forgot');
        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_wrong_codes_lock_out_verification_and_resending_does_not_reset_limit(): void
    {
        $user = User::factory()->create();
        $otp = $this->startReset($user);
        $wrong = $otp === '000000' ? '111111' : '000000';
        for ($i = 0; $i < 5; $i++) {
            $this->post('/password/verify', ['otp' => $wrong])->assertSessionHasErrors('otp');
        }
        $this->travel(61)->seconds();
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp])->assertSessionHasErrors('otp');
        $this->assertNull(session('reset_grant'));
    }

    public function test_password_dispatch_has_a_cooldown(): void
    {
        $user = User::factory()->create();
        $this->startReset($user);
        $this->post('/password/forgot', ['email' => $user->email])->assertSessionHasErrors('email');
        Mail::assertSentCount(1);
        $this->assertNull(session('reset_grant'));
    }

    public function test_replaced_reset_record_invalidates_an_old_grant(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp]);
        DB::table('password_reset_otps')->where('email', $user->email)->delete();
        $this->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_changing_challenge_invalidates_verified_grant(): void
    {
        $user = User::factory()->create();
        $oldPassword = $user->password;
        $otp = $this->startReset($user);
        $this->post('/password/verify', ['otp' => $otp]);
        $this->withSession(['reset_challenge' => 'another-challenge'])
            ->post('/password/reset', $this->passwordInput())->assertRedirect('/password/forgot');
        $this->assertSame($oldPassword, $user->fresh()->password);
    }

    public function test_failed_email_delivery_leaves_no_usable_challenge(): void
    {
        $user = User::factory()->create();
        Mail::shouldReceive('to')->once()->with($user->email)->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Delivery failed'));
        $this->post('/password/forgot', ['email' => $user->email])
            ->assertSessionHas('error')->assertSessionMissing('success');
        $this->assertNull(session('reset_email'));
        $this->assertNull(session('reset_grant'));
        $this->assertDatabaseMissing('password_reset_otps', ['email' => $user->email]);
    }
}
