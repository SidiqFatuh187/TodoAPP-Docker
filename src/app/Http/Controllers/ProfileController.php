<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;

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
            $user->phone = $request->phone;
            $user->bio   = $request->bio;
            $user->save();

            // Trigger welcome
            if ($request->phone && !$oldPhone) {
                $this->sendWhatsAppWelcome($request->phone, $user->name);
            }
            // Ganti nomer 
            elseif ($request->phone && $oldPhone && $request->phone !== $oldPhone){
                $this->sendWhatsAppConfirmChange($request->phone, $user->name);
            }

            return redirect()->route('profile.index')->with('success', 'Profil berhasil diperbarui.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memperbarui profil, coba lagi.');
        }
    }

    private function sendWhatsAppWelcome($phone, $name)
    {
        try {
            Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                'target'  => $phone,
                'message' => "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu berhasil terhubung dengan *Claro App*.\n\nMulai sekarang kamu akan menerima pengingat otomatis setiap ada task yang mendekati deadline.\n\nBalas pesan ini dengan *OK* untuk memastikan notifikasi berjalan lancar 🎉",
            ]);
        } catch (\Exception $e) {
            logger()->error('Gagal kirim WA welcome: ' . $e->getMessage());
        }
    }

    private function sendWhatsAppConfirmChange($phone, $name)
    {
        try {
            Http::withHeaders([
                'Authorization' => env('FONNTE_TOKEN'),
            ])->post('https://api.fonnte.com/send', [
                'target'  => $phone,
                'message' => "Halo *{$name}*! 👋\n\nNomor WhatsApp kamu di *Claro App* berhasil diperbarui.\n\nNotifikasi deadline akan dikirim ke nomor ini mulai sekarang ✅",
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