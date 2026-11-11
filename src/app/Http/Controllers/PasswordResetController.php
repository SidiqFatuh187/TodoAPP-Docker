<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Mail\OtpMail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
   
    public function forgot()
    {
        return view('password.forgot');
    }

    
    public function sendOtp(Request $request)
    {
        $request->session()->forget(['reset_email', 'reset_record', 'reset_challenge', 'reset_grant', 'otp_verified']);

        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.exists'   => 'Email tidak terdaftar.',
        ]);

        $email = $request->string('email')->toString();
        $sendKey = 'password-otp-send:' . strtolower($email);
        $ipKey = 'password-otp-send-ip:' . $request->ip();
        if (RateLimiter::tooManyAttempts($sendKey, 1) || RateLimiter::tooManyAttempts($ipKey, 5)) {
            return back()->withErrors(['email' => 'Tunggu sebelum meminta kode OTP baru.'])->withInput($request->only('email'));
        }
        RateLimiter::hit($sendKey, 60);
        RateLimiter::hit($ipKey, 600);

        DB::table('password_reset_otps')->where('email', $email)->delete();
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user = User::where('email', $email)->firstOrFail();
        $recordId = DB::table('password_reset_otps')->insertGetId([
            'email' => $email,
            'otp' => $otp,
            'expired_at' => now()->addMinutes(5),
            'used' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            Mail::to($email)->send(new OtpMail($otp, $user->name));
        } catch (\Exception $e) {
            DB::table('password_reset_otps')->where('id', $recordId)->delete();
            report($e);
            return back()->with('error', 'Gagal mengirim email, coba lagi.')->withInput($request->only('email'));
        }

        session([
            'reset_email' => $email,
            'reset_record' => $recordId,
            'reset_challenge' => Str::random(40),
        ]);

        return redirect()->route('password.verify')->with('success', 'Kode OTP telah dikirim ke email kamu.');
    }

   
    public function verify()
    {
        if (!session('reset_email')) {
            return redirect()->route('password.forgot');
        }

        return view('password.verify');
    }

   
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus 6 digit.',
        ]);

        $email = session('reset_email');

        if (!$email) {
            return redirect()->route('password.forgot');
        }

        $attemptKey = 'password-otp-verify:' . strtolower($email);
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return back()->withErrors(['otp' => 'Terlalu banyak percobaan. Silakan coba lagi nanti.']);
        }

        $record = DB::table('password_reset_otps')
            ->where('id', session('reset_record'))
            ->where('email', $email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->first();

        if (!$record || !session('reset_challenge')) {
            RateLimiter::hit($attemptKey, 600);
            return back()->withErrors(['otp' => 'Kode OTP tidak valid.']);
        }

        if (now()->greaterThanOrEqualTo($record->expired_at)) {
            DB::table('password_reset_otps')->where('id', $record->id)->delete();
            session()->forget(['reset_grant', 'reset_record', 'reset_challenge']);
            return redirect()->route('password.forgot')->with('error', 'Kode OTP sudah expired, minta kode baru.');
        }

        $claimed = DB::table('password_reset_otps')->where('id', $record->id)
            ->where('used', false)->update(['used' => true, 'updated_at' => now()]);
        if (!$claimed) {
            return back()->withErrors(['otp' => 'Kode OTP tidak valid.']);
        }
        RateLimiter::clear($attemptKey);
        session(['reset_grant' => [
            'email' => $email,
            'record' => $record->id,
            'challenge' => session('reset_challenge'),
            'expires_at' => min(now()->addMinutes(5)->timestamp, Carbon::parse($record->expired_at)->timestamp),
        ]]);

        return redirect()->route('password.reset');
    }

  
    public function reset()
    {
        if (!$this->validGrant()) {
            return redirect()->route('password.forgot');
        }

        return view('password.reset');
    }

   
    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ], [
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $grant = session('reset_grant');
        if (!$this->validGrant()) {
            session()->forget('reset_grant');
            return redirect()->route('password.forgot');
        }

        try {
            $password = Hash::make($request->password);
            $updated = DB::transaction(function () use ($grant, $password) {
                $record = DB::table('password_reset_otps')->where('id', $grant['record'])
                    ->where('email', $grant['email'])->where('used', true)->lockForUpdate()->first();
                if (!$record || now()->timestamp >= $grant['expires_at']
                    || now()->greaterThanOrEqualTo($record->expired_at)) {
                    return false;
                }
                User::where('email', $grant['email'])->update([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ]);
                DB::table('password_reset_otps')->where('id', $record->id)->delete();
                return true;
            });
            if (!$updated) {
                session()->forget('reset_grant');
                return redirect()->route('password.forgot')->with('error', 'Sesi reset telah berakhir. Minta kode baru.');
            }

            session()->forget(['reset_email', 'reset_record', 'reset_challenge', 'reset_grant', 'otp_verified']);
            session()->invalidate();
            session()->regenerateToken();

            return redirect()->route('login')->with('success', 'Password berhasil direset, silakan login dengan password baru kamu.');
        } catch (\Exception $e) {
            logger()->error('Password reset failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal update password, coba lagi.');
        }
    }

    private function validGrant(): bool
    {
        $grant = session('reset_grant');
        return is_array($grant)
            && isset($grant['email'], $grant['record'], $grant['challenge'], $grant['expires_at'])
            && $grant['email'] === session('reset_email')
            && $grant['record'] === session('reset_record')
            && $grant['challenge'] === session('reset_challenge')
            && now()->timestamp < $grant['expires_at'];
    }
}