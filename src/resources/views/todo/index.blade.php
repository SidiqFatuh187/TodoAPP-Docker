@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
<div class="max-w-5xl mx-auto">

    <x-modal-delete title="Hapus Task?" />

    {{-- Alert Success --}}
    @if(session('success'))
        <div class="mb-5 p-4 rounded-xl bg-green-50 border border-green-200 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
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

   {{-- Action Bar --}}
<div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
    <div class="flex items-center gap-2 flex-wrap">
        <p class="text-sm text-gray-400">Total <span class="font-semibold text-gray-600">{{ $todos->count() }}</span> tasks</p>

        {{-- Filter Status --}}
        <div class="flex gap-1.5">
            <a href="{{ route('todo.index', array_merge(request()->query(), ['status' => ''])) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ !request('status') ? 'bg-blue-600 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">
                Semua
            </a>
            <a href="{{ route('todo.index', array_merge(request()->query(), ['status' => 'pending'])) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status') === 'pending' ? 'bg-yellow-500 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">
                Pending
            </a>
            <a href="{{ route('todo.index', array_merge(request()->query(), ['status' => 'active'])) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status') === 'active' ? 'bg-blue-500 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">
                Active
            </a>
            <a href="{{ route('todo.index', array_merge(request()->query(), ['status' => 'completed'])) }}"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors {{ request('status') === 'completed' ? 'bg-green-500 text-white' : 'bg-white text-gray-500 border border-gray-200 hover:bg-gray-50' }}">
                Completed
            </a>
        </div>

        {{-- Filter Kategori --}}
        <select onchange="window.location.href=this.value"
            class="px-3 py-1.5 rounded-lg text-xs border border-gray-200 text-gray-500 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 transition">
            <option value="{{ route('todo.index', array_merge(request()->query(), ['category' => ''])) }}">
                Semua Kategori
            </option>
            @foreach($category as $cat)
                <option value="{{ route('todo.index', array_merge(request()->query(), ['category' => $cat->id])) }}"
                    {{ request('category') == $cat->id ? 'selected' : '' }}>
                    {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}
                </option>
            @endforeach
        </select>
    </div>

        <a href="{{ route('todo.create') }}"
            class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Tambah Task
        </a>
    </div>
    
    {{-- Empty State --}}
    @if($todos->isEmpty())
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
            <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                </svg>
            </div>
            <p class="text-gray-700 font-medium">Belum ada task</p>
            <p class="text-gray-400 text-sm mt-1">Mulai dengan menambahkan task pertamamu!</p>
            <a href="{{ route('todo.create') }}"
                class="inline-flex items-center gap-2 mt-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Task
            </a>
        </div>

    {{-- Todo List --}}
    @else
        <div class="flex flex-col gap-3">
            @foreach($todos as $todo)
                <div class="todo-item-row bg-white rounded-2xl border border-gray-100 shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">

                  {{-- Status Toggle --}}
                    @php
                        $toggleClass = match($todo->status) {
                            'pending'   => 'border-gray-300 hover:border-yellow-400 bg-white',
                            'active'    => 'border-blue-400 bg-blue-400',
                            'completed' => 'border-green-500 bg-green-500',
                        };
                    @endphp
                    <button type="button"
                        onclick="updateStatus({{ $todo->id }}, '{{ $todo->status }}', this)"
                        title="{{ ucfirst($todo->status) }}"
                        class="shrink-0 w-6 h-6 rounded-full border-2 flex items-center justify-center transition-all {{ $toggleClass }}">
                        @if($todo->status === 'completed')
                            <svg class="w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                            </svg>
                        @elseif($todo->status === 'active')
                            <span class="w-2 h-2 bg-white rounded-full"></span>
                        @endif
                    </button>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                           <a href="{{ route('todo.show', $todo->id) }}"
                            class="todo-title text-sm font-semibold text-gray-700 hover:text-blue-600 transition-colors {{ $todo->status === 'completed' ? 'line-through text-gray-400' : '' }}">
                                {{ $todo->title }}
                            </a>

                            {{-- Priority Badge --}}
                            @php
                                $priorityClass = match($todo->priority) {
                                    'high'   => 'bg-red-50 text-red-500',
                                    'medium' => 'bg-yellow-50 text-yellow-600',
                                    'low'    => 'bg-green-50 text-green-600',
                                    default  => 'bg-gray-50 text-gray-500',
                                };
                            @endphp
                            <span class="px-2 py-0.5 rounded-lg text-xs font-medium {{ $priorityClass }}">
                                {{ ucfirst($todo->priority) }}
                            </span>

                            {{-- Status Badge --}}
                            @php
                                $statusClass = match($todo->status) {
                                    'completed' => 'bg-green-50 text-green-600',
                                    'active'    => 'bg-blue-50 text-blue-600',
                                    'pending'   => 'bg-yellow-50 text-yellow-600',
                                    default     => 'bg-gray-50 text-gray-500',
                                };
                            @endphp
                            <span class="todo-status-badge px-2 py-0.5 rounded-lg text-xs font-medium {{ $statusClass }}">
                                {{ ucfirst($todo->status) }}
                            </span>
                        </div>

                        {{-- Meta --}}
                        <div class="flex items-center gap-3 mt-1.5 flex-wrap">
                            {{-- Category --}}
                            @if($todo->category)
                                <span class="flex items-center gap-1 text-xs text-gray-400">
                                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $todo->category->color }}"></span>
                                    {{ $todo->category->name }}
                                </span>
                            @endif

                            {{-- Deadline --}}
                            @if($todo->deadline)
                                @php
                                    $isOverdue = $todo->deadline->isPast() && $todo->status !== 'completed';
                                    $deadlineClass = $isOverdue ? 'text-red-500' : 'text-gray-400';
                                @endphp
                                <span class="flex items-center gap-1 text-xs {{ $deadlineClass }}">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    {{ $isOverdue ? 'Telat · ' : '' }}{{ $todo->deadline->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('d M Y') }}
                                </span>
                            @endif

                            {{-- Description --}}
                            @if($todo->description)
                                <span class="text-xs text-gray-400 truncate max-w-xs">{{ $todo->description }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- File Attachment --}}  
                    @if($todo->file_path)
                        <a href="{{ route('todo.download', $todo->id) }}" target="_blank"
                            class="flex items-center gap-1 text-xs text-blue-500 hover:text-blue-600 transition-colors">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                            </svg>
                            {{ $todo->file_name }}
                        </a>
                    @endif

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
            @endforeach
        </div>
    @endif

</div>

@push('scripts')
    @vite('resources/js/todo-index.js')
@endpush
@endsection