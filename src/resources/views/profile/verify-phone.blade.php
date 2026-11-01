@extends('layouts.app')

@section('title', 'Verifikasi WhatsApp - Wassup Task')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Alert Messages --}}
    @if(session('info'))
        <div class="mb-5 p-4 rounded-xl bg-blue-50 border border-blue-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-blue-700">{{ session('info') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm text-red-700">{{ session('error') }}</p>
        </div>
    @endif

    <div class="flex flex-col md:flex-row gap-4 md:gap-6 items-start">

        @include('profile.partials.sidebar')

        {{-- Main --}}
        <div class="flex-1 min-w-0 w-full">
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-8">
                <div class="w-12 h-12 bg-green-50 text-green-600 rounded-2xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.845L.057 23.143a.75.75 0 00.922.899l5.453-1.43A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.886 0-3.65-.52-5.157-1.424l-.36-.215-3.733.979.998-3.645-.234-.374A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                </div>

                <h2 class="text-lg font-bold text-gray-800 mb-1">Verifikasi Nomor WhatsApp</h2>
                <p class="text-sm text-gray-500 mb-6">
                    Kami telah mengirimkan 4-digit kode verifikasi ke nomor <span class="font-bold text-gray-800">{{ $pendingPhone }}</span>. Masukkan kode tersebut untuk mengonfirmasi kepemilikan nomor.
                </p>

                <form action="{{ route('profile.verify-phone.submit') }}" method="POST" class="max-w-md">
                    @csrf

                    <div class="mb-4">
                        <label for="otp" class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-2">
                            Kode OTP (4 Digit)
                        </label>
                        <input type="text"
                               id="otp"
                               name="otp"
                               maxlength="4"
                               pattern="[0-9]*"
                               inputmode="numeric"
                               placeholder="1234"
                               autofocus
                               required
                               class="w-full text-center tracking-[0.5em] text-2xl font-bold px-4 py-3 rounded-xl border border-gray-200 text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        @error('otp')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="submit"
                            class="px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition-colors cursor-pointer shadow-xs">
                            Verifikasi & Simpan
                        </button>
                    </div>
                </form>

                <div class="mt-6 pt-5 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 max-w-md">
                    <span>Belum menerima pesan WhatsApp?</span>
                    <form action="{{ route('profile.resend-phone-otp') }}" method="POST">
                        @csrf
                        <button type="submit" class="text-blue-600 hover:underline font-semibold cursor-pointer">
                            Kirim Ulang Kode
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
