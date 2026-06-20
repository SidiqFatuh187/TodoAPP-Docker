@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="max-w-5xl mx-auto">

    {{-- Alert Success --}}
    @if(session('success'))
        <div class="mb-5 p-4 rounded-xl bg-green-50 border border-green-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
            <p class="text-sm text-green-700">{{ session('success') }}</p>
        </div>
    @endif

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

        {{-- Main Content --}}
        <div class="flex-1 min-w-0 w-full flex flex-col gap-4">

            {{-- Avatar Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <p class="text-sm font-semibold text-gray-800 mb-4">My Profile</p>
                <div class="flex items-center gap-3 md:gap-4">
                    {{-- Avatar --}}
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
                        <p class="text-xs md:text-sm text-gray-400">{{ ucfirst(auth()->user()->role ?? 'User') }}</p>
                        @if(auth()->user()->phone)
                            <div class="flex items-center gap-1 mt-1 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                </svg>
                                {{ auth()->user()->phone }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Personal Info Card --}}
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-4 md:p-6">
                <div class="flex items-center justify-between mb-5">
                    <p class="text-sm font-semibold text-gray-800">Personal Information</p>
                    <a href="{{ route('profile.edit') }}"
                        class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition-colors">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit
                    </a>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-5">
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Nama</p>
                        <p class="text-sm text-gray-700 break-words">{{ auth()->user()->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">No. Telepon</p>
                        <p class="text-sm text-gray-700">{{ auth()->user()->phone ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Email</p>
                        <p class="text-sm text-gray-700 break-words">{{ auth()->user()->email }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Role</p>
                        <p class="text-sm text-gray-700">{{ ucfirst(auth()->user()->role ?? 'User') }}</p>
                    </div>
                    <div class="sm:col-span-2">
                        <p class="text-xs text-gray-400 font-medium uppercase tracking-wide mb-1">Bio</p>
                        <p class="text-sm text-gray-700 break-words">{{ auth()->user()->bio ?? '-' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection