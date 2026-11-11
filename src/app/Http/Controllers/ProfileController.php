<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
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
            'phone' => ['nullable', 'string', 'regex:/^(?:0|\+?62)8[0-9]{8,12}$/'],
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
        $newPhone = $request->phone ? $this->normalizePhone($request->phone) : null;
        if ($newPhone && $this->phoneInUse($newPhone, $user->id)) {
            return back()->withErrors(['phone' => 'Nomor WhatsApp sudah digunakan.'])
                ->with('error', 'Nomor WhatsApp sudah digunakan.')->withInput($request->only('name', 'email', 'phone', 'bio'));
        }
        $phoneChanged = $newPhone && $newPhone !== $this->normalizePhone($oldPhone ?? '');
        if ($phoneChanged) {
            $this->checkPhoneCooldown($newPhone);
        }

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
                $this->clearPhoneChallenge();
                return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
            }

            // Jika nomor HP tidak berubah
            if (!$phoneChanged) {
                $user->save();
                $this->clearPhoneChallenge();
                return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
            }

            // Simpan perubahan profil dasar terlebih dahulu
            $user->save();

            $this->dispatchPhoneOtp($newPhone, $user->name);

            return redirect()->route('profile.verify-phone')->with('info', "Kode verifikasi 4 digit telah dikirim ke WhatsApp {$newPhone}.");
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Gagal memperbarui profil atau mengirim kode WhatsApp, coba lagi.')
                ->withInput($request->only('name', 'email', 'phone', 'bio'));
        }
    }

    public function verifyPhoneView()
    {
        if (!session('pending_phone') || session('phone_otp_user') !== Auth::id()) {
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
        $sessionOtp = session('phone_otp');
        $expiresAt = session('phone_otp_expires');
        $attemptKey = 'phone-otp-verify:' . Auth::id();
        if (!$pendingPhone || !$sessionOtp || !$expiresAt || session('phone_otp_user') !== Auth::id()) {
            return redirect()->route('profile.edit')->with('error', 'Sesi verifikasi telah berakhir. Silakan masukkan nomor kembali.');
        }
        if (RateLimiter::tooManyAttempts($attemptKey, 5)) {
            return back()->with('error', 'Terlalu banyak percobaan. Silakan coba lagi nanti.');
        }
        if (now()->greaterThanOrEqualTo($expiresAt)) {
            return back()->with('error', 'Kode verifikasi telah kadaluwarsa. Silakan kirim ulang.');
        }
        if (!hash_equals((string) $sessionOtp, (string) $request->otp)) {
            RateLimiter::hit($attemptKey, 600);
            return back()->with('error', 'Kode verifikasi salah.');
        }

        $user = Auth::user();
        $isFirstTime = empty($user->phone);
        try {
            Cache::lock('profile-phone-ownership', 10)->block(5, function () use ($pendingPhone, $user) {
                DB::transaction(function () use ($pendingPhone, $user) {
                    if ($this->phoneInUse($pendingPhone, $user->id)) {
                        throw ValidationException::withMessages(['phone' => 'Nomor WhatsApp sudah digunakan.']);
                    }
                    $user->phone = $this->normalizePhone($pendingPhone);
                    $user->save();
                });
            });
        } catch (ValidationException $e) {
            $this->clearPhoneChallenge();
            return redirect()->route('profile.edit')->withErrors($e->errors())->with('error', 'Nomor WhatsApp sudah digunakan.');
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Gagal menyimpan nomor WhatsApp, coba lagi.');
        }

        RateLimiter::clear($attemptKey);
        $this->clearPhoneChallenge();
        try {
            if ($isFirstTime) {
                $this->sendWhatsAppWelcome($user->phone, $user->name);
            } else {
                $this->sendWhatsAppConfirmChange($user->phone, $user->name);
            }
        } catch (\Exception $e) {
            report($e);
            return redirect()->route('profile.index')->with('success', 'Nomor WhatsApp berhasil diverifikasi dan terhubung!')
                ->with('error', 'Pesan konfirmasi WhatsApp gagal dikirim.');
        }
        return redirect()->route('profile.index')->with('success', 'Nomor WhatsApp berhasil diverifikasi dan terhubung!');
    }

    public function resendPhoneOtp()
    {
        $pendingPhone = session('pending_phone');
        if (!$pendingPhone || session('phone_otp_user') !== Auth::id()) {
            return redirect()->route('profile.edit');
        }

        if ($this->phoneInUse($pendingPhone, Auth::id())) {
            $this->clearPhoneChallenge();
            return redirect()->route('profile.edit')->withErrors(['phone' => 'Nomor WhatsApp sudah digunakan.'])
                ->with('error', 'Nomor WhatsApp sudah digunakan.');
        }
        $this->checkPhoneCooldown($pendingPhone);
        try {
            $this->dispatchPhoneOtp($pendingPhone, Auth::user()->name);
        } catch (\Exception $e) {
            report($e);
            return back()->with('error', 'Gagal mengirim kode WhatsApp, coba lagi.');
        }

        return back()->with('info', "Kode verifikasi baru telah dikirim ke WhatsApp {$pendingPhone}.");
    }

    private function sendPhoneOtp(string $phone, string $otp, string $name): void
    {
        $this->sendWhatsApp($phone, "🔒 *Wassup Task*\n\nHalo *{$name}*! Kode verifikasi untuk menghubungkan nomor WhatsApp kamu adalah: *{$otp}*\n\nKode ini berlaku selama 10 menit. Jangan berikan kode ini kepada siapa pun.");
    }

    private function sendWhatsAppWelcome($phone, $name)
    {
        $this->sendWhatsApp($phone, "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu berhasil terhubung dengan *Wassup Task*.\n\nMulai sekarang kamu akan menerima pengingat otomatis setiap ada task yang mendekati deadline.\n\nBalas pesan ini dengan *OK* untuk memastikan notifikasi berjalan lancar 🎉");
    }

    private function sendWhatsAppConfirmChange($phone, $name)
    {
        $this->sendWhatsApp($phone, "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu di *Wassup Task* berhasil diperbarui.\n\nNotifikasi deadline akan dikirim ke nomor ini mulai sekarang ✅");
    }

    private function sendWhatsApp(string $phone, string $message): void
    {
        $response = Http::withHeaders(['Authorization' => config('services.fonnte.token')])
            ->timeout(15)->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                'target' => $phone,
                'message' => $message,
            ]);
        $response->throw();
        if ($response->json('status') !== true) {
            throw new \RuntimeException('Fonnte rejected the WhatsApp message.');
        }
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        return str_starts_with($phone, '0') ? '62' . substr($phone, 1) : $phone;
    }

    private function phoneInUse(string $phone, int $userId): bool
    {
        $phone = $this->normalizePhone($phone);
        return User::where('id', '!=', $userId)->whereIn('phone', [
            $phone, '+' . $phone, '0' . substr($phone, 2),
        ])->exists();
    }

    private function checkPhoneCooldown(string $phone): void
    {
        if (RateLimiter::tooManyAttempts('phone-otp-send-user:' . Auth::id(), 1)
            || RateLimiter::tooManyAttempts('phone-otp-send-number:' . $phone, 1)) {
            session()->flash('error', 'Tunggu 60 detik sebelum meminta kode baru.');
            throw ValidationException::withMessages(['phone' => 'Tunggu 60 detik sebelum meminta kode baru.']);
        }
    }

    private function dispatchPhoneOtp(string $phone, string $name): void
    {
        RateLimiter::hit('phone-otp-send-user:' . Auth::id(), 60);
        RateLimiter::hit('phone-otp-send-number:' . $phone, 60);
        $this->clearPhoneChallenge();
        $otp = (string) random_int(1000, 9999);
        $this->sendPhoneOtp($phone, $otp, $name);
        session([
            'pending_phone' => $phone,
            'phone_otp' => $otp,
            'phone_otp_expires' => now()->addMinutes(10),
            'phone_otp_user' => Auth::id(),
        ]);
    }

    private function clearPhoneChallenge(): void
    {
        session()->forget(['pending_phone', 'phone_otp', 'phone_otp_expires', 'phone_otp_user']);
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