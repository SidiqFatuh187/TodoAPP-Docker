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
                            No. Telepon <span class="normal-case text-gray-300">(opsional)</span>
                        </label>
                        <input type="text" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}"
                            placeholder="08xxxxxxxxxx"
                            class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                    </div>

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