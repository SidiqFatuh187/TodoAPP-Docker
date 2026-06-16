<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lupa Password - ToDoApp</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 via-blue-200 to-indigo-300 p-4">

    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden flex min-h-[520px]">

        {{-- Left Panel --}}
        <div class="hidden md:flex w-1/2 bg-gradient-to-br from-blue-200 via-indigo-200 to-blue-300 items-center justify-center p-12 relative">
            {{-- Blob decorations --}}
            <div class="absolute top-6 left-6 w-24 h-24 bg-blue-300 opacity-40 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 right-4 w-32 h-32 bg-indigo-300 opacity-30 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <h2 class="text-3xl font-bold text-gray-800 leading-tight mb-3">
                   Lupa<br>Password?
                </h2>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Tenang, kami bantu kamu reset.<br>
                    Cukup masukkan email terdaftar.
                </p>
            </div>
        </div>

        {{-- Right Panel --}}
        <div class="w-full md:w-1/2 flex items-center justify-center p-10">
            <div class="w-full max-w-sm">

                {{-- Icon --}}
                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center mb-5">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>

                <h2 class="text-2xl font-bold text-gray-800 mb-1">Lupa Password?</h2>
                <p class="text-gray-400 text-sm mb-7">
                    Masukkan email kamu, kami akan kirimkan kode OTP untuk reset password.
                </p>

                {{-- Alert Success --}}
                @if(session('success'))
                    <div class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-green-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-green-700">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Alert Error --}}
                @if(session('error'))
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <p class="text-sm text-red-700">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('password.forgot') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-600 mb-1.5">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            class="w-full border @error('email') border-red-400 bg-red-50 @else border-gray-200 @enderror rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                            autofocus
                            required
                        >
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl transition-colors duration-200 mt-4 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Kirim Kode OTP
                    </button>
                </form>

                {{-- Back to Login --}}
                <p class="text-center text-sm text-gray-400 mt-5">
                    <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-700 font-medium inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Kembali ke Login
                    </a>
                </p>

            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="absolute bottom-4 w-full flex justify-center gap-6 text-xs text-gray-400">
        <a href="#" class="hover:text-gray-600">Terms</a>
        <a href="#" class="hover:text-gray-600">Plans</a>
        <a href="#" class="hover:text-gray-600">Contact Us</a>
    </div>

</body>
</html>