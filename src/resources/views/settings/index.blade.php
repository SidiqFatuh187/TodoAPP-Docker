@extends('layouts.app')

@section('title', 'Settings')

@section('content')
<div class="max-w-5xl mx-auto">
    <x-alert />

    <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-start">

        {{-- Sidebar --}}
        <aside class="w-full md:w-52 bg-white border border-gray-100 rounded-2xl shadow-sm py-3 md:py-5 shrink-0 overflow-x-auto">
            <p class="hidden md:block text-sm font-semibold text-gray-800 px-5 pb-4 border-b border-gray-100 mb-2">Settings</p>

            <div class="flex md:flex-col gap-1 md:gap-0 px-3 md:px-0 whitespace-nowrap">
                <a href="{{ route('settings.index') }}"
                    class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2 text-blue-600 font-medium bg-blue-50 md:border-blue-600">
                     <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    <span class="md:inline">Setting</span>
                </a>

                <a href="{{ route('profile.edit') }}"
                     class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2 text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Setting Profile
                </a>
                <a href="#keamanan"
                    class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2 text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 10-8 0v4h8z"/>
                    </svg>
                    Akun & Keamanan
                </a>
                <a href="#preferensi"
                    class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2 text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Preferensi Aplikasi
                </a>
            </div>
        </aside>

        {{-- Main Content --}}
        <div class="flex-1 min-w-0 w-full flex flex-col gap-4">

            {{-- Profil Card --}}
            <div id="profil" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm font-semibold text-gray-800">Profil</p>
                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                </div>

                <div class="flex items-center gap-3 md:gap-4">
                    @if(auth()->user()->avatar)
                        <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                            class="w-12 h-12 md:w-14 md:h-14 rounded-full object-cover shrink-0">
                    @else
                        <div class="w-12 h-12 md:w-14 md:h-14 rounded-full flex items-center justify-center text-white text-base md:text-lg font-bold shrink-0"
                            style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(strstr(auth()->user()->name, ' ') ?: ' ', 1, 1)) }}
                        </div>
                    @endif
                    <div class="min-w-0">
                        <p class="text-sm md:text-base font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                        <p class="text-xs md:text-sm text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            {{-- Akun & Keamanan Card --}}
            <div id="keamanan" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <p class="text-sm font-semibold text-gray-800 mb-5">Akun & Keamanan</p>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 py-3 border-b border-gray-100">
                    <div>
                        <p class="text-sm text-gray-700">Password</p>
                        <p class="text-xs text-gray-400 mt-0.5">Ubah password akun secara berkala untuk keamanan</p>
                    </div>
                    <a href="{{ route('profile.password') }}"
                        class="text-xs font-medium text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors shrink-0 self-start sm:self-auto">
                        Ganti Password
                    </a>
                </div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 py-3">
                    <div>
                        <p class="text-sm text-gray-700">Verifikasi Email</p>
                        <p class="text-xs text-gray-400 mt-0.5 truncate">{{ auth()->user()->email }}</p>
                    </div>
                    @if(auth()->user()->email_verified_at)
                        <span class="text-xs font-medium text-green-600 bg-green-50 px-3 py-1.5 rounded-lg shrink-0 self-start sm:self-auto">Terverifikasi</span>
                    @else
                        <span class="text-xs font-medium text-yellow-600 bg-yellow-50 px-3 py-1.5 rounded-lg shrink-0 self-start sm:self-auto">Belum Verifikasi</span>
                    @endif
                </div>
            </div>

            {{-- Preferensi Aplikasi Card --}}
            <div id="preferensi" class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <p class="text-sm font-semibold text-gray-800 mb-5">Preferensi Aplikasi</p>

                <form action="{{ route('settings.update') }}" method="POST" class="flex flex-col gap-5">
                    @csrf

                    <div>
                        <label class="block text-xs text-gray-400 font-medium uppercase tracking-wide mb-1.5">Zona Waktu</label>
                        <select name="timezone"
                            class="w-full border border-gray-200 rounded-xl px-3.5 py-2 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                            <option value="Asia/Jakarta" {{ (auth()->user()->timezone ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' }}>WIB (Jakarta)</option>
                            <option value="Asia/Makassar" {{ (auth()->user()->timezone ?? '') == 'Asia/Makassar' ? 'selected' : '' }}>WITA (Makassar)</option>
                            <option value="Asia/Jayapura" {{ (auth()->user()->timezone ?? '') == 'Asia/Jayapura' ? 'selected' : '' }}>WIT (Jayapura)</option>
                        </select>
                    </div>

                    <button type="submit"
                        class="w-full sm:w-auto sm:self-start bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors">
                        Simpan Preferensi
                    </button>
                </form>
            </div>

        </div>
    </div>
</div>
@endsection