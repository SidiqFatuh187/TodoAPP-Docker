<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-100 via-blue-200 to-indigo-300 p-4">

    <div class="w-full max-w-md text-center">

        {{-- Card --}}
        <div class="bg-white rounded-3xl shadow-2xl p-10 md:p-12">

            {{-- Illustration --}}
            <div class="w-20 h-20 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-6">
                <svg class="w-10 h-10 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>

            {{-- Error code --}}
            <p class="text-7xl font-bold text-indigo-600 leading-none mb-2">404</p>

            {{-- Title --}}
            <h1 class="text-xl font-bold text-gray-800 mt-4 mb-2">Halaman Tidak Ditemukan</h1>

            {{-- Description --}}
            <p class="text-sm text-gray-400 leading-relaxed mb-8">
                Halaman yang kamu cari tidak ada atau sudah dipindahkan.<br>
                Yuk kembali ke halaman utama.
            </p>

            {{-- Actions --}}
            <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}"
                        class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                        Ke Dashboard
                    </a>
                @else
                    <a href="{{ route('login') }}"
                        class="w-full sm:w-auto bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                        Ke Halaman Login
                    </a>
                @endauth

                <button onclick="history.back()"
                    class="w-full sm:w-auto border border-gray-200 text-gray-500 hover:bg-gray-50 text-sm font-medium px-6 py-2.5 rounded-xl transition-colors">
                    Kembali
                </button>
            </div>

        </div>

        {{-- Branding --}}
        <p class="text-xs text-gray-400 mt-6">CLARO App &copy; {{ date('Y') }}</p>

    </div>

</body>
</html>