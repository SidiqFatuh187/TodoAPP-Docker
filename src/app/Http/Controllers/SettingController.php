<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        return view('settings.index');
    }
    
    public function update(Request $request)
    {
        $request->validate([
            'timezone' => 'required|in:Asia/Jakarta,Asia/Makassar,Asia/Jayapura',
        ]);

        $user = auth()->user();
        try {
        $user->timezone = $request->timezone;
        $user->save();

         return redirect()->back()->with('success', 'Pengaturan berhasil disimpan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menyimpan pengaturan, coba lagi.');
        }
    }
}
