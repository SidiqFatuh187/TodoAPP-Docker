<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
class ProfileController extends Controller
{
    public function index()
    {
        $title    = 'Profile';
        $subtitle = 'Profil Saya';

        return view('profile.index', compact('title', 'subtitle'));
    }

    public function edit()
    {
        $title    = 'Profile';
        $subtitle = 'Edit Profile';

        return view('profile.edit', compact('title', 'subtitle'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:users,email,' . Auth::id(),
            'phone' => 'nullable|digits_between:10,15',
            'bio'    => 'nullable|string|max:500',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ], [
            'name.required'  => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique'   => 'Email sudah digunakan.',
            'avatar.image'   => 'File harus berupa gambar.',
            'avatar.max'     => 'Ukuran foto maksimal 2MB.',
        ]);

        $user = Auth::user();
        $oldPhone = $user->phone;
        $newPhone = $request->phone ? preg_replace('/\D/', '', $request->phone) : null;

        try {
            if ($request->hasFile('avatar')) {
                // Hapus avatar lama
                if ($user->avatar) {
                    Storage::disk('public')->delete($user->avatar);
                }
                $user->avatar = $request->file('avatar')->store('avatars', 'public');
            }

            $user->name  = $request->name;
            $user->email = $request->email;
            $user->bio   = $request->bio;

            // Jika nomor HP dikosongkan
            if (empty($newPhone)) {
                $user->phone = null;
                $user->save();
                session()->forget(['pending_phone', 'phone_otp', 'phone_otp_expires']);
                return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
            }

            // Jika nomor HP tidak berubah
            if ($newPhone === $oldPhone) {
                $user->save();
                return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
            }

            // Simpan perubahan profil dasar terlebih dahulu
            $user->save();

            // Generate OTP 4 digit untuk nomor baru
            $otp = (string) random_int(1000, 9999);
            session([
                'pending_phone'     => $newPhone,
                'phone_otp'         => $otp,
                'phone_otp_expires' => Carbon::now()->addMinutes(10),
            ]);

            $this->sendPhoneOtp($newPhone, $otp, $user->name);

            return redirect()->route('profile.verify-phone')->with('info', "Kode verifikasi 4 digit telah dikirim ke WhatsApp {$newPhone}.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui profil, coba lagi.');
        }
    }

    public function verifyPhoneView()
    {
        if (!session('pending_phone')) {
            return redirect()->route('profile.index');
        }

        $title    = 'Profile';
        $subtitle = 'Verifikasi WhatsApp';
        $pendingPhone = session('pending_phone');

        return view('profile.verify-phone', compact('title', 'subtitle', 'pendingPhone'));
    }

    public function verifyPhoneSubmit(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:4',
        ], [
            'otp.required' => 'Kode OTP wajib diisi.',
            'otp.digits'   => 'Kode OTP harus berupa 4 digit angka.',
        ]);

        $pendingPhone = session('pending_phone');
        $sessionOtp   = session('phone_otp');
        $expiresAt    = session('phone_otp_expires');

        if (!$pendingPhone || !$sessionOtp) {
            return redirect()->route('profile.edit')->with('error', 'Sesi verifikasi telah berakhir. Silakan masukkan nomor kembali.');
        }

        if (Carbon::now()->gt($expiresAt)) {
            return back()->with('error', 'Kode verifikasi telah kadaluwarsa. Silakan kirim ulang.');
        }

        if ($request->otp !== $sessionOtp) {
            return back()->with('error', 'Kode verifikasi salah.');
        }

        $user = Auth::user();
        $isFirstTime = empty($user->phone);
        $user->phone = $pendingPhone;
        $user->save();

        session()->forget(['pending_phone', 'phone_otp', 'phone_otp_expires']);

        if ($isFirstTime) {
            $this->sendWhatsAppWelcome($user->phone, $user->name);
        } else {
            $this->sendWhatsAppConfirmChange($user->phone, $user->name);
        }

        return redirect()->route('profile.index')->with('success', 'Nomor WhatsApp berhasil diverifikasi dan terhubung!');
    }

    public function resendPhoneOtp()
    {
        $pendingPhone = session('pending_phone');
        if (!$pendingPhone) {
            return redirect()->route('profile.edit');
        }

        $otp = (string) random_int(1000, 9999);
        session([
            'phone_otp'         => $otp,
            'phone_otp_expires' => Carbon::now()->addMinutes(10),
        ]);

        $this->sendPhoneOtp($pendingPhone, $otp, Auth::user()->name);

        return back()->with('info', "Kode verifikasi baru telah dikirim ke WhatsApp {$pendingPhone}.");
    }

    private function sendPhoneOtp(string $phone, string $otp, string $name): void
    {
        try {
            Http::withHeaders([
                'Authorization' => config('services.fonnte.token'),
            ])->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                'target'  => $phone,
                'message' => "🔒 *Wassup Task*\n\nHalo *{$name}*! Kode verifikasi untuk menghubungkan nomor WhatsApp kamu adalah: *{$otp}*\n\nKode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.",
            ]);
        } catch (\Throwable $e) {
            logger()->error('Gagal kirim WA OTP: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppWelcome($phone, $name)
    {
        try {
            Http::withHeaders([
                'Authorization' => config('services.fonnte.token'),
            ])->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                'target'  => $phone,
                'message' => "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu berhasil terhubung dengan *Wassup Task*.\n\nMulai sekarang kamu akan menerima pengingat otomatis setiap ada task yang mendekati deadline.\n\nBalas pesan ini dengan *OK* untuk memastikan notifikasi berjalan lancar 🎉",
            ]);
        } catch (\Exception $e) {
            logger()->error('Gagal kirim WA welcome: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppConfirmChange($phone, $name)
    {
        try {
            Http::withHeaders([
                'Authorization' => config('services.fonnte.token'),
            ])->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                'target'  => $phone,
                'message' => "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu di *Wassup Task* berhasil diperbarui.\n\nNotifikasi deadline akan dikirim ke nomor ini mulai sekarang ✅",
            ]);
        } catch (\Exception $e) {
            logger()->error('Gagal kirim WA confirm change: ' . $e->getMessage());
        }
    }

    public function passwordForm()
    {
        $title    = 'Profile';
        $subtitle = 'Ganti Password';

        return view('profile.password', compact('title', 'subtitle'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required'         => 'Password baru wajib diisi.',
            'password.min'              => 'Password minimal 8 karakter.',
            'password.confirmed'        => 'Konfirmasi password tidak cocok.',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->withErrors([
                'current_password' => 'Password lama tidak sesuai.'
            ]);
        }

        try {
            $user->password = Hash::make($request->password);
            $user->save();

            return redirect()->route('profile.index')->with('success', 'Password berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengganti password, coba lagi.');
        }
    }
}