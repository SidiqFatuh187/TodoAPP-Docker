{{-- Sidebar --}}
<aside class="w-full md:w-52 bg-white border border-gray-100 rounded-2xl shadow-sm py-3 md:py-5 shrink-0 overflow-x-auto">
    <p class="hidden md:block text-sm font-semibold text-gray-800 px-5 pb-4 border-b border-gray-100 mb-2">Account Settings</p>

    <div class="flex md:flex-col gap-1 md:gap-0 px-3 md:px-0 whitespace-nowrap">
        <a href="{{ route('profile.index') }}"
            class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2
            {{ request()->routeIs('profile.index') ? 'text-blue-600 font-medium bg-blue-50 md:border-blue-600' : 'text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            My Profile
        </a>
        <a href="{{ route('profile.edit') }}"
            class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2
            {{ request()->routeIs('profile.edit') ? 'text-blue-600 font-medium bg-blue-50 md:border-blue-600' : 'text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Profile
        </a>
        <a href="{{ route('profile.password') }}"
            class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2
            {{ request()->routeIs('profile.password') ? 'text-blue-600 font-medium bg-blue-50 md:border-blue-600' : 'text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
            Ganti Password
        </a>
        <a href="{{ route('settings.index') }}"
            class="flex items-center gap-2.5 px-4 md:px-5 py-2.5 text-sm rounded-xl md:rounded-none transition-colors md:border-r-2
            {{ request()->routeIs('settings.index') ? 'text-blue-600 font-medium bg-blue-50 md:border-blue-600' : 'text-gray-500 md:border-transparent hover:bg-gray-50 hover:text-gray-700' }}">
            <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            Settings
        </a>
    </div>
</aside>