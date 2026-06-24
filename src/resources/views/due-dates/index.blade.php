@extends('layouts.app')

@section('title', 'Due Dates')
@section('page-title', 'Due Dates')

@section('content')
<div class="max-w-5xl mx-auto">

    <x-modal-delete title="Hapus Task?" />

    {{-- Overdue --}}
    @if($overdue->count() > 0)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-red-500 shrink-0"></span>
            <h3 class="text-sm font-semibold text-red-500">Overdue</h3>
            <span class="text-xs text-red-400 bg-red-50 px-2 py-0.5 rounded-lg font-medium">{{ $overdue->count() }}</span>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($overdue as $todo)
                @include('due-dates.partials.task-item', ['todo' => $todo, 'accent' => 'red'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Today --}}
    @if($today->count() > 0)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-amber-500 shrink-0"></span>
            <h3 class="text-sm font-semibold text-amber-600">Hari Ini</h3>
            <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-lg font-medium">{{ $today->count() }}</span>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($today as $todo)
                @include('due-dates.partials.task-item', ['todo' => $todo, 'accent' => 'amber'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Tomorrow --}}
    @if($tomorrow->count() > 0)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-blue-400 shrink-0"></span>
            <h3 class="text-sm font-semibold text-blue-500">Besok</h3>
            <span class="text-xs text-blue-500 bg-blue-50 px-2 py-0.5 rounded-lg font-medium">{{ $tomorrow->count() }}</span>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($tomorrow as $todo)
                @include('due-dates.partials.task-item', ['todo' => $todo, 'accent' => 'blue'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Later --}}
    @if($later->count() > 0)
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-3">
            <span class="w-2 h-2 rounded-full bg-gray-400 shrink-0"></span>
            <h3 class="text-sm font-semibold text-gray-500">Selanjutnya</h3>
            <span class="text-xs text-gray-500 bg-gray-100 px-2 py-0.5 rounded-lg font-medium">{{ $later->count() }}</span>
        </div>
        <div class="flex flex-col gap-3">
            @foreach($later as $todo)
                @include('due-dates.partials.task-item', ['todo' => $todo, 'accent' => 'gray'])
            @endforeach
        </div>
    </div>
    @endif

    {{-- Empty State --}}
   @if($overdue->isEmpty() && $today->isEmpty() && $tomorrow->isEmpty() && $later->isEmpty())
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-12 text-center">
        <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-8 h-8 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
        </div>
        <p class="text-gray-700 font-medium">Tidak ada task dengan deadline</p>
        <p class="text-gray-400 text-sm mt-1">Semua task kamu tidak memiliki deadline atau sudah selesai.</p>
        <a href="{{ route('todo.index') }}"
            class="inline-flex items-center gap-2 mt-4 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-xl transition-colors">
            Lihat semua task
        </a>
    </div>
    @endif

</div>

@push('scripts')
    @vite('resources/js/todo-index.js')
@endpush
@endsection