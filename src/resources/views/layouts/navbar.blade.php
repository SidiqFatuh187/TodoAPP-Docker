<header class="bg-white border-b border-gray-100 px-4 md:px-6 py-3.5 flex items-center justify-between sticky top-0 z-20">

    {{-- Hamburger (mobile) --}}
    <button onclick="toggleSidebar()" class="text-gray-500 hover:text-gray-700 md:hidden shrink-0">
        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
    </button>

    {{-- Page Title --}}
    <h2 class="text-base font-semibold text-gray-700 truncate">@yield('page-title', 'CLARO')</h2>

    {{-- Right --}}
    <div class="flex items-center gap-2 md:gap-4">

        {{-- Search (hidden on mobile) --}}
        <div class="relative hidden md:block" id="search-wrapper">
            <form action="{{ route('todo.index') }}" method="GET" autocomplete="off">
                <input
                    type="text"
                    id="navbar-search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Search tasks..."
                    class="pl-9 pr-4 py-2 text-sm border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-400 w-48 bg-gray-50"
                >
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </form>

            {{-- Dropdown suggest --}}
            <div id="search-dropdown"
                class="hidden absolute top-full left-0 mt-1.5 w-72 bg-white rounded-xl border border-gray-100 shadow-lg z-50 overflow-hidden">
                <div id="search-results" class="py-1"></div>
            </div>
        </div>

        {{-- Search icon only (mobile) --}}
        <a href="{{ route('todo.index') }}" class="text-gray-400 hover:text-gray-600 md:hidden">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </a>

        {{-- Bell Icon + Notification Popup --}}
        @php
            $notifications = auth()->user()->notifications()->latest()->take(10)->get();
            $unreadCount   = auth()->user()->unreadNotifications()->count();
        @endphp

        <div class="relative flex items-center" id="notif-wrapper">
            <button onclick="toggleNotif()" class="relative text-gray-400 hover:text-gray-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                {{-- Badge --}}
                @if($unreadCount > 0)
                <span class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
                @endif
            </button>

            {{-- Popup --}}
            <div id="notif-dropdown"
                class="hidden absolute right-0 top-full mt-2 w-[calc(100vw-2rem)] max-w-80 bg-white rounded-xl border border-gray-200 shadow-lg z-50 overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-700">Notification</p>
                    @if($unreadCount > 0)
                    <form id="markAllReadForm" action="{{ route('notification.markAllRead') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                    <button onclick="document.getElementById('markAllReadForm').submit();" class="text-xs text-blue-500 hover:text-blue-600 font-medium">
                        Mark all as read
                    </button>
                    @endif
                </div>

                {{-- List --}}
                <div class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                    @forelse($notifications as $notif)
                    @php $data = $notif->data; @endphp
                    <div class="flex items-start gap-3 px-4 py-3 {{ is_null($notif->read_at) ? 'bg-blue-50/40' : '' }} hover:bg-gray-50 transition-colors">

                        {{-- Dot --}}
                        <div class="mt-1.5 shrink-0">
                            @if(is_null($notif->read_at))
                                <div class="w-2 h-2 rounded-full
                                    {{ ($data['type'] ?? '') === 'overdue' ? 'bg-red-400' : (($data['type'] ?? '') === 'due_today' ? 'bg-amber-400' : 'bg-blue-400') }}">
                                </div>
                            @else
                                <div class="w-2 h-2 rounded-full bg-gray-200"></div>
                            @endif
                        </div>

                        {{-- Content --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm text-gray-700 leading-snug">{{ $data['message'] ?? '' }}</p>
                            <p class="text-[11px] text-gray-400 mt-0.5">{{ $notif->created_at->diffForHumans() }}</p>
                        </div>

                        {{-- Delete --}}
                        <button onclick="openDeleteModal('{{ route('notification.destroy', $notif->id) }}', 'this notification')"
                            class="shrink-0 text-gray-300 hover:text-red-400 transition-colors mt-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    @empty
                    <div class="py-10 text-center text-sm text-gray-400">
                        No notifications yet.
                    </div>
                    @endforelse
                </div>

                {{-- View all --}}
                <div class="px-4 py-2.5 border-t border-gray-100">
                    <a href="{{ route('notifications.index') }}"
                        class="text-xs font-semibold text-blue-500 hover:text-blue-600 flex items-center justify-center gap-1">
                        Lihat semua notifikasi →
                    </a>
                </div>

            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="text-gray-400 hover:text-gray-600 hidden sm:block">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
            </svg>
        </a>

        {{-- Admin Panel Button --}}
        @if(auth()->user()->role === 'admin')
            <a href="{{ route('admin.index') }}"
               class="hidden lg:flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-700 bg-indigo-50 border border-indigo-200 rounded-lg hover:bg-indigo-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Admin Panel
            </a>
        @endif

      {{-- User --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open"
                class="flex items-center gap-2.5 pl-1 pr-2 md:pr-3 py-1 rounded-xl hover:bg-violet-50 transition-all duration-200 group">
                @if(auth()->user()->avatar)
                    <img src="{{ asset('storage/' . auth()->user()->avatar) }}"
                        class="w-8 h-8 rounded-lg object-cover flex-shrink-0">
                @else
                    <div class="w-8 h-8 rounded-lg flex items-center justify-center font-bold text-sm text-white flex-shrink-0"
                        style="background: linear-gradient(135deg, #7c6fef, #a78bfa);">
                        {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                    </div>
                @endif
                <div class="hidden lg:block text-left">
                    <p class="text-sm font-semibold text-gray-800 leading-tight">{{ auth()->user()->name ?? 'User' }}</p>
                    <p class="text-xs text-gray-400 leading-tight">{{ ucfirst(auth()->user()->role) }}</p>
                </div>
                <svg class="w-3.5 h-3.5 text-gray-400 transition-transform duration-200 group-hover:text-violet-500 hidden sm:block"
                    :class="open ? 'rotate-180' : ''"
                    fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            {{-- Dropdown --}}
            <div x-show="open"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 scale-95 -translate-y-1"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-100"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                @click.outside="open = false"
                class="absolute right-0 top-full mt-2 w-52 bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden z-50"
                style="box-shadow: 0 10px 40px rgba(124, 111, 239, 0.15);">

                {{-- User info --}}
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-bold text-gray-800">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>

                {{-- Menu --}}
                <div class="py-1.5">
                    <a href="{{ route('profile.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-violet-50 hover:text-violet-700 transition-colors duration-150">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Profil Saya
                    </a>
                    @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-violet-50 hover:text-violet-700 transition-colors duration-150 lg:hidden">
                        <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Admin Panel
                    </a>
                    @endif
                </div>

                {{-- Logout --}}
                <div class="border-t border-gray-100 py-1.5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-500 hover:bg-red-50 transition-colors duration-150 text-left">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    @push('scripts')
        <script src="{{ Vite::asset('resources/js/notifications.js') }}"></script>
    @endpush
</header>