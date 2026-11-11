@extends('layouts.app')

@section('title', 'Admin - Tasks')
@section('page-title', 'Task Overview')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

@section('content')

{{-- Page Header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ now()->format('l, d F Y') }}</p>
    </div>
    <a href="{{ route('admin.export') }}"
       class="flex items-center gap-2 px-3.5 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        Export CSV
    </a>
</div>

{{-- Horizontal Admin Nav --}}
<nav class="flex items-center gap-1 mb-6 bg-white border border-gray-200 rounded-xl p-1 w-fit">
    @php
        $navLinks = [
            ['route' => 'admin.index',    'label' => 'Overview',     'icon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['route' => 'admin.users',    'label' => 'Users',        'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
            ['route' => 'admin.tasks',    'label' => 'All Tasks',    'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4'],
            ['route' => 'admin.category','label' => 'Category',     'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z'],
            ['route' => 'admin.activity','label' => 'Activity Log', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
        ];
    @endphp

    @foreach($navLinks as $nav)
    <a href="{{ route($nav['route']) }}"
       class="flex items-center gap-2 px-3.5 py-2 rounded-lg text-sm font-medium transition-colors
              {{ request()->routeIs($nav['route'])
                  ? 'bg-gray-900 text-white'
                  : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50' }}">
        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $nav['icon'] }}"/>
        </svg>
        {{ $nav['label'] }}
    </a>
    @endforeach
</nav>

{{-- Section Header + Filter --}}
<div class="flex items-center justify-between mb-4">
    <div>
        <p class="text-[11px] font-semibold tracking-widest uppercase text-gray-400">All Tasks</p>
        <p class="text-sm font-semibold text-gray-700 mt-0.5">{{ $tasks->total() }} tasks total</p>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('admin.tasks') }}" class="flex items-center gap-2">

        {{-- Filter by User --}}
        <select name="user_id"
            class="text-xs border border-gray-200 rounded-xl px-3 py-2 bg-white text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
            <option value="">All users</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>

        {{-- Filter by Status --}}
        <select name="status"
            class="text-xs border border-gray-200 rounded-xl px-3 py-2 bg-white text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-300">
            <option value="">All status</option>
            <option value="pending"   {{ request('status') === 'pending'   ? 'selected' : '' }}>Pending</option>
            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
        </select>

        <button type="submit"
            class="px-3.5 py-2 text-xs font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
            Filter
        </button>

        @if(request('user_id') || request('status'))
            <a href="{{ route('admin.tasks') }}"
               class="px-3.5 py-2 text-xs font-semibold border border-gray-200 rounded-xl text-gray-500 hover:bg-gray-50">
                Clear
            </a>
        @endif
    </form>
</div>

{{-- Privacy Notice --}}
<div class="flex items-center gap-2 mb-4 px-4 py-2.5 bg-amber-50 border border-amber-200 rounded-xl w-fit">
    <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
    </svg>
    <p class="text-xs text-amber-700 font-medium">Task title & description are hidden to protect user privacy.</p>
</div>

{{-- Table --}}
<div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
    <table class="w-full">
        <thead>
            <tr class="bg-gray-50 border-b border-gray-100">
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">User</th>
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">Category</th>
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">Status</th>
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">Priority</th>
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">Deadline</th>
                <th class="text-left text-[10px] font-semibold text-gray-400 uppercase tracking-widest px-5 py-3">Created</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
            @forelse($tasks as $task)
            <tr class="hover:bg-gray-50 transition-colors">

                {{-- User --}}
                <td class="px-5 py-3.5">
                    <div class="flex items-center gap-2.5">
                        <div class="w-7 h-7 rounded-lg shrink-0 flex items-center justify-center text-[10px] font-bold bg-gray-900 text-white [font-family:'JetBrains_Mono',monospace]">
                            {{ strtoupper(substr($task->user->name ?? '?', 0, 2)) }}
                        </div>
                        <p class="text-sm font-medium text-gray-700">{{ $task->user->name ?? '—' }}</p>
                    </div>
                </td>

                {{-- Category --}}
                <td class="px-5 py-3.5">
                    @if($task->category)
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-gray-100 text-gray-600 tracking-wide">
                            {{ $task->category->name }}
                        </span>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>

                {{-- Status --}}
                <td class="px-5 py-3.5">
                    @if($task->status === 'completed')
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-emerald-50 text-emerald-600 tracking-wide">completed</span>
                    @elseif($task->deadline && $task->deadline->isPast())
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-red-50 text-red-500 tracking-wide">overdue</span>
                    @else
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-amber-50 text-amber-600 tracking-wide">pending</span>
                    @endif
                </td>

                {{-- Priority --}}
                <td class="px-5 py-3.5">
                    @if($task->priority === 'high')
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-red-50 text-red-500 tracking-wide">high</span>
                    @elseif($task->priority === 'medium')
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-amber-50 text-amber-600 tracking-wide">medium</span>
                    @else
                        <span class="text-[10px] font-semibold px-2 py-0.5 rounded-lg bg-gray-100 text-gray-500 tracking-wide">low</span>
                    @endif
                </td>

                {{-- Deadline --}}
                <td class="px-5 py-3.5">
                    @if($task->deadline)
                        <span class="text-xs [font-family:'JetBrains_Mono',monospace] {{ $task->deadline->isPast() && $task->status !== 'completed' ? 'text-red-400' : 'text-gray-400' }}">
                            {{ $task->deadline->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('d M Y') }}
                        </span>
                    @else
                        <span class="text-xs text-gray-300">—</span>
                    @endif
                </td>

                {{-- Created --}}
                <td class="px-5 py-3.5">
                    <span class="text-xs [font-family:'JetBrains_Mono',monospace] text-gray-400">
                        {{ $task->created_at->format('d M Y') }}
                    </span>
                </td>

            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-5 py-10 text-center text-sm text-gray-400">
                    No tasks found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($tasks->hasPages())
    <div class="px-5 py-3.5 border-t border-gray-100">
        {{ $tasks->appends(request()->query())->links() }}
    </div>
    @endif
</div>

@endsection