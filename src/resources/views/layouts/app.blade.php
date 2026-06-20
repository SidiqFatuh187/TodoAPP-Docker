<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
     <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ToDoApp')</title>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
  @vite(['resources/css/app.css', 'resources/js/layoutsApp.js', 'resources/js/navbar-search.js', 'resources/js/notifications.js'])
</head>
<body class="bg-gray-50 min-h-screen flex">
    <x-modal-delete />

    @include('layouts.sidebar')

    {{-- Overlay (mobile only, shown when sidebar open) --}}
    <div id="sidebar-overlay" onclick="toggleSidebar()"
        class="hidden fixed inset-0 bg-black/40 z-20 md:hidden"></div>

    <div class="flex-1 md:ml-64 flex flex-col min-h-screen w-full">
        @include('layouts.navbar')

        <main class="flex-1 p-4 md:p-6">
            @if(isset($title))
            <div class="mb-6">
                <h3 class="text-xl font-bold text-gray-800">{{ $title }}</h3>
                @if(isset($subtitle))
                <p class="text-sm text-gray-400 mt-1">{{ $subtitle }}</p>
                @endif
            </div>
            @endif
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>