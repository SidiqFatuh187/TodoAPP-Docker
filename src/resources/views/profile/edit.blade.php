@extends('layouts.app')

@section('title', 'Edit Profile')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Alert Error --}}
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
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <p class="text-sm font-semibold text-gray-800 mb-6">Edit Profile</p>

                <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
                    @csrf
                    @method('PUT')

                    {{-- Avatar Upload --}}
                    <div>
                        <label class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-3">Foto Profil</label>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-5">

                            {{-- Preview --}}
                            <div id="avatar-preview-wrapper" class="shrink-0">
                                @if(auth()->user()->avatar)
                                    <img id="avatar-preview"
                                        src="{{ asset('storage/' . auth()->user()->avatar) }}"
                                        class="w-16 h-16 rounded-full object-cover">
                                @else
                                    <div id="avatar-initials"
                                        class="w-16 h-16 rounded-full flex items-center justify-center text-white text-xl font-bold"
                                        style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}{{ strtoupper(substr(strstr(auth()->user()->name, ' ') ?: ' ', 1, 1)) }}
                                    </div>
                                    <img id="avatar-preview" src="" class="w-16 h-16 rounded-full object-cover hidden">
                                @endif
                            </div>

                            {{-- Upload button --}}
                            <div>
                                <label for="avatar"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-50 hover:bg-blue-100 text-blue-600 text-sm font-medium rounded-xl cursor-pointer transition-colors">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Pilih Foto
                                </label>
                                <input type="file" id="avatar" name="avatar" accept="image/*" class="hidden">
                                <p class="text-xs text-gray-400 mt-1.5">PNG, JPG — maks. 2MB</p>
                                <p id="avatar-filename" class="text-xs text-blue-600 mt-1 hidden truncate max-w-[200px]"></p>
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-gray-100"></div>

                    {{-- Nama --}}
                    <div>
                        <label for="name" class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">Nama</label>
                        <input type="text" id="name" name="name" value="{{ old('name', auth()->user()->name) }}"
                            class="w-full px-4 py-2.5 rounded-xl border @error('name') border-red-400 bg-red-50 @else border-gray-200 @enderror text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email', auth()->user()->email) }}"
                        class="w-full px-4 py-2.5 rounded-xl border @error('email') border-red-400 bg-red-50 @else border-gray-200 @enderror text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        @error('email')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    {{-- Phone --}}
                    <div>
                        <label for="phone" class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                            No. WhatsApp <span class="normal-case text-amber-500 font-semibold">Wajib untuk notifikasi</span>
                        </label>
                        <input type="tel" id="phone" name="phone" 
                                value="{{ old('phone', auth()->user()->phone) }}"
                                placeholder="08xxxxxxxxxx"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <p class="text-xs text-gray-400 mt-1.5">
                            Isi nomor WA agar kamu menerima reminder otomatis saat deadline mendekat.
                        </p>

                        <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-xl">
                            <p class="text-xs text-blue-700">
                                💡 Setelah disimpan, kamu akan menerima pesan konfirmasi otomatis di WhatsApp. 
                                Pastikan untuk <span class="font-semibold">membalas pesan tersebut</span> agar notifikasi selanjutnya masuk dengan lancar.
                            </p>
                        </div>
                    </div>
                    
                    <a href="https://wa.me/6285764694530?text=Halo%20Claro%20App%2C%20aktifkan%20notifikasi%20saya" 
                    target="_blank"
                    class="inline-flex items-center gap-2 mt-3 px-4 py-2.5 bg-green-500 hover:bg-green-600 text-white text-xs font-semibold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.845L.057 23.143a.75.75 0 00.922.899l5.453-1.43A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.886 0-3.65-.52-5.157-1.424l-.36-.215-3.733.979.998-3.645-.234-.374A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                        </svg>
                        Aktifkan Notifikasi WhatsApp
                    </a>
                    <p class="text-xs text-gray-400 mt-1.5">
                        Klik tombol di atas untuk mengaktifkan notifikasi — kamu akan diarahkan ke WhatsApp, cukup kirim pesan yang sudah tersedia.
                    </p>

                    {{-- Bio --}}
                    <div>
                        <label for="bio" class="block text-xs font-medium text-gray-400 uppercase tracking-wide mb-1.5">
                            Bio <span class="normal-case text-gray-300">(opsional)</span>
                        </label>
                        <textarea id="bio" name="bio" rows="3"
                            placeholder="Ceritakan sedikit tentang dirimu..."
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('bio', auth()->user()->bio) }}</textarea>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 pt-1">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            Simpan Perubahan
                        </button>
                        <a href="{{ route('profile.index') }}"
                            class="px-5 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-colors text-center">
                            Batal
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Preview avatar sebelum upload
    document.getElementById('avatar').addEventListener('change', function () {
        var file = this.files[0];
        if (!file) return;

        var preview = document.getElementById('avatar-preview');
        var initials = document.getElementById('avatar-initials');
        var filename = document.getElementById('avatar-filename');

        var reader = new FileReader();
        reader.onload = function (e) {
            preview.src = e.target.result;
            preview.classList.remove('hidden');
            if (initials) initials.classList.add('hidden');
        };
        reader.readAsDataURL(file);

        if (filename) {
            filename.textContent = file.name;
            filename.classList.remove('hidden');
        }
    });
</script>
@endpush
@endsection