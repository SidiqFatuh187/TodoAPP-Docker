@extends('layouts.app')

@section('title', 'Detail Task')

@section('content')
<div class="max-w-lg mx-auto">

    {{-- Back --}}
    <div class="mb-6">
        <a href="{{ route('todo.index') }}"
            class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-gray-600 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali ke Tasks
        </a>
    </div>

    {{-- Card --}}
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 flex flex-col gap-5">

        {{-- Header --}}
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1 min-w-0">
                <h2 class="text-lg font-bold text-gray-800 {{ $todo->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                    {{ $todo->title }}
                </h2>
                <p class="text-xs text-gray-400 mt-1">Dibuat {{ $todo->created_at->diffForHumans() }}</p>
            </div>

            {{-- Actions --}}
            <div class="flex items-center gap-1.5 shrink-0">
                <a href="{{ route('todo.edit', $todo->id) }}"
                    class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-blue-50 flex items-center justify-center text-gray-400 hover:text-blue-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>
                <button type="button"
                    onclick="openDeleteModal('{{ route('todo.delete', $todo->id) }}', '{{ $todo->title }}')"
                    class="w-8 h-8 rounded-lg bg-gray-50 hover:bg-red-50 flex items-center justify-center text-gray-400 hover:text-red-500 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="border-t border-gray-100"></div>

        {{-- Badges --}}
        <div class="flex items-center gap-2 flex-wrap">

            {{-- Status --}}
            @php
                $statusClass = match($todo->status) {
                    'completed' => 'bg-green-50 text-green-600',
                    'active'    => 'bg-blue-50 text-blue-600',
                    'pending'   => 'bg-yellow-50 text-yellow-600',
                    default     => 'bg-gray-50 text-gray-500',
                };
                $statusIcon = match($todo->status) {
                    'completed' => '✅',
                    'active'    => '🔵',
                    'pending'   => '⏳',
                    default     => '—',
                };
            @endphp
            <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $statusClass }}">
                {{ $statusIcon }} {{ ucfirst($todo->status) }}
            </span>

            {{-- Priority --}}
            @php
                $priorityClass = match($todo->priority) {
                    'high'   => 'bg-red-50 text-red-500',
                    'medium' => 'bg-yellow-50 text-yellow-600',
                    'low'    => 'bg-green-50 text-green-600',
                    default  => 'bg-gray-50 text-gray-500',
                };
                $priorityIcon = match($todo->priority) {
                    'high'   => '🔴',
                    'medium' => '🟡',
                    'low'    => '🟢',
                    default  => '—',
                };
            @endphp
            <span class="px-2.5 py-1 rounded-lg text-xs font-medium {{ $priorityClass }}">
                {{ $priorityIcon }} {{ ucfirst($todo->priority) }}
            </span>

            {{-- Category --}}
            @if($todo->category)
                <span class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-gray-50 text-gray-600">
                    <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $todo->category->color }}"></span>
                    {{ $todo->category->icon ? $todo->category->icon . ' ' : '' }}{{ $todo->category->name }}
                </span>
            @endif

        </div>

        {{-- Deadline --}}
        @if($todo->deadline)
            @php
                $isOverdue = $todo->deadline->isPast() && $todo->status !== 'completed';
            @endphp
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 {{ $isOverdue ? 'text-red-400' : 'text-gray-400' }} shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <div>
                    <p class="text-xs text-gray-400">Deadline</p>
                    <p class="text-sm font-medium {{ $isOverdue ? 'text-red-500' : 'text-gray-700' }}">
                        {{ $todo->deadline->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('d M Y, H:i') }}
                        @if($isOverdue)
                            <span class="text-xs font-normal">(Telat)</span>
                        @endif
                    </p>
                </div>
            </div>
        @endif

        {{-- Completed At --}}
        @if($todo->completed_at)
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-green-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div>
                    <p class="text-xs text-gray-400">Diselesaikan</p>
                    <p class="text-sm font-medium text-gray-700">{{ $todo->completed_at->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('d M Y, H:i') }}</p>
                </div>
            </div>
        @endif

        {{-- Description --}}
        @if($todo->description)
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-400 mb-2">Deskripsi</p>
                <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $todo->description }}</p>
            </div>
        @endif

        {{-- File Attachment --}}
        @if($todo->file_path)
            <div class="border-t border-gray-100 pt-4">
                <p class="text-xs text-gray-400 mb-2">Lampiran</p>
                <a href="{{ route('todo.download', $todo->id) }}" target="_blank"
                    class="inline-flex items-center gap-2 px-3 py-2 rounded-xl border border-gray-200 text-sm text-blue-500 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                    <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                    </svg>
                    {{ $todo->file_name }}
                </a>
            </div>
        @endif

    </div>
</div>

<x-modal-delete title="Hapus Task?" />

@endsection