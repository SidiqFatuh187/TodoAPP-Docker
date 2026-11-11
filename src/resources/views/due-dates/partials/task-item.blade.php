@php
    $accentBorder = match($accent) {
        'red'    => 'border-l-red-400',
        'amber'  => 'border-l-amber-400',
        'blue'   => 'border-l-blue-400',
        'indigo' => 'border-l-indigo-400',
        default  => 'border-l-gray-300',
    };
@endphp

<div class="todo-item-row bg-white rounded-2xl border border-gray-100 border-l-4 {{ $accentBorder }} shadow-sm p-5 flex items-center gap-4 hover:shadow-md transition-shadow">

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
            @if($todo->category)
                <span class="flex items-center gap-1 text-xs text-gray-400">
                    <span class="w-2 h-2 rounded-full" style="background-color: {{ $todo->category->color }}"></span>
                    {{ $todo->category->name }}
                </span>
            @endif

            @if($todo->deadline)
                @php
                    $isOverdue = $todo->deadline->isPast() && $todo->status !== 'completed';
                    $deadlineClass = $isOverdue ? 'text-red-500' : 'text-gray-400';
                @endphp
                <span class="flex items-center gap-1 text-xs {{ $deadlineClass }}">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    {{ $isOverdue ? 'Telat · ' : '' }}{{ $todo->deadline->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('d M Y, H:i') }}
                </span>
            @endif

            @if($todo->description)
                <span class="text-xs text-gray-400 truncate max-w-xs">{{ $todo->description }}</span>
            @endif
        </div>
    </div>

    {{-- File --}}
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