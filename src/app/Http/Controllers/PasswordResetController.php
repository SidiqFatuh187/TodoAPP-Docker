<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Mail\OtpMail;
use Carbon\Carbon;
use DB;

class PasswordResetController extends Controller
{
   
    public function forgot()
    {
        return view('password.forgot');
    }

    
    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
            'email.exists'   => 'Email tidak terdaftar.',
        ]);

        // Hapus OTP lama
        DB::table('password_reset_otps')->where('email', $request->email)->delete();

        // Generate OTP 6 digit
        $otp  = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $user = User::where('email', $request->email)->first();

        // Simpan OTP ke database
        DB::table('password_reset_otps')->insert([
            'email'      => $request->email,
            'otp'        => $otp,
            'expired_at' => Carbon::now()->addMinutes(5),
            'used'       => false,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        // Kirim email
        try {
            Mail::to($request->email)->send(new OtpMail($otp, $user->name));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengirim email, coba lagi.');
        }

        // Simpan email di session untuk step berikutnya
        session(['reset_email' => $request->email]);

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

        $record = DB::table('password_reset_otps')
            ->where('email', $email)
            ->where('otp', $request->otp)
            ->where('used', false)
            ->first();

        // OTP tidak ditemukan
        if (!$record) {
            return redirect()->back()->withErrors(['otp' => 'Kode OTP tidak valid.']);
        }

        // OTP expired
        if (Carbon::now()->isAfter($record->expired_at)) {
            DB::table('password_reset_otps')->where('email', $email)->delete();
            return redirect()->route('password.forgot')->with('error', 'Kode OTP sudah expired, minta kode baru.');
        }

        // Tandai OTP sudah dipakai
        DB::table('password_reset_otps')->where('email', $email)->update(['used' => true]);

        // Simpan flag verified di session
        session(['otp_verified' => true]);

        return redirect()->route('password.reset');
    }

  
    public function reset()
    {
        if (!session('reset_email') || !session('otp_verified')) {
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

        $email = session('reset_email');

        if (!$email || !session('otp_verified')) {
            return redirect()->route('password.forgot');
        }

        try {
            User::where('email', $email)->update([
                'password' => Hash::make($request->password),
            ]);

            // Hapus OTP dari database
            DB::table('password_reset_otps')->where('email', $email)->delete();
            
            // Clear password reset session dengan sempurna
            session()->forget(['reset_email', 'otp_verified']);
            session()->regenerate();
            session()->invalidate();

            return redirect()->route('login')->with('success', 'Password berhasil direset, silakan login dengan password baru kamu.');
        } catch (\Exception $e) {
            logger()->error('Password reset failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal update password, coba lagi.');
        }
    }
}