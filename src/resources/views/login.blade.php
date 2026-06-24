<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ToDoApp</title>
    @vite(['resources/css/app.css', 'resources/js/login.js'])
</head>
<body class="min-h-screen flex flex-col items-center justify-center bg-gradient-to-br from-blue-100 via-blue-200 to-indigo-300 p-4 gap-4">

    <div class="w-full max-w-4xl bg-white rounded-3xl shadow-2xl overflow-hidden flex">

        {{-- Left Panel --}}
        <div class="hidden md:flex w-1/2 bg-gradient-to-br from-blue-200 via-indigo-200 to-blue-300 items-center justify-center p-12 relative">
            <div class="absolute top-6 left-6 w-24 h-24 bg-blue-300 opacity-40 rounded-full blur-2xl"></div>
            <div class="absolute bottom-10 right-4 w-32 h-32 bg-indigo-300 opacity-30 rounded-full blur-3xl"></div>

            <div class="relative z-10">
                <h2 class="text-3xl font-bold text-gray-800 leading-tight mb-3">
                   Cepat, Efisien<br>dan Produktif
                </h2>
                <p class="text-gray-600 text-sm leading-relaxed">
                    Kelola tugas harian Anda dengan mudah.<br>
                    Tetap fokus dan selesaikan pekerjaan.
                </p>
            </div>
        </div>

        {{-- Right Panel --}}
        <div class="w-full md:w-1/2 flex items-center justify-center p-6 md:p-10">
            <div class="w-full max-w-sm">

                <h2 class="text-2xl font-bold text-gray-800 mb-1">Sign In</h2>
                <p class="text-gray-400 text-sm mb-7">Welcome back to CLARO</p>

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
                @if ($errors->any())
                    <div class="mb-4 p-3 rounded-lg bg-red-50 border border-red-200">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 text-red-500 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                            <div class="text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <p>{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form --}}
                <form action="{{ route('login') }}" method="POST">
                    @csrf

                    {{-- Email --}}
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-600 mb-1.5">Email</label>
                        <input
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                            required
                        >
                    </div>

                    {{-- Password --}}
                    <div class="mb-3">
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-sm font-medium text-gray-600">Password</label>
                            <a href="{{ route('password.forgot') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">
                                Lupa password?
                            </a>
                        </div>
                        <div class="relative">
                            <input
                                type="password"
                                name="password"
                                id="password"
                                placeholder="••••••••"
                                class="w-full border border-gray-200 rounded-xl px-4 py-2.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent transition"
                                required
                            >
                            <button type="button" onclick="togglePass('password', 'eye-off-1', 'eye-on-1')" class="absolute right-3 top-2.5 text-gray-400 hover:text-gray-600">
                                <svg id="eye-off-1" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a9.956 9.956 0 012.223-3.592M6.53 6.533A9.956 9.956 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.07 5.292M15 12a3 3 0 11-4.243-4.243M3 3l18 18" />
                                </svg>
                                <svg id="eye-on-1" class="w-4 h-4 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Use 8 or more characters with a mix of letters, numbers & symbols.</p>
                    </div>

                    {{-- Submit --}}
                    <button type="submit"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2.5 rounded-xl transition-colors duration-200 mt-4">
                        Sign In
                    </button>

                </form>

                {{-- Register Link --}}
                <p class="text-center text-sm text-gray-400 mt-5">
                    Don't have an account?
                    <a href="{{ route('register') }}" class="text-indigo-600 hover:text-indigo-700 font-medium">Register</a>
                </p>

            </div>
        </div>

    </div>

    {{-- Footer --}}
    <div class="flex justify-center gap-6 text-xs text-gray-400 pb-2">
        <a href="#" class="hover:text-gray-600">Terms</a>
        <a href="#" class="hover:text-gray-600">Plans</a>
        <a href="#" class="hover:text-gray-600">Contact Us</a>
    </div>

</body>
</html>