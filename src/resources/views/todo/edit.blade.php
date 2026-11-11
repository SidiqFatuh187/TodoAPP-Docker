@extends('layouts.app')

@section('title', 'Edit Task')

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
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">

        <form action="{{ route('todo.update', $todo->id) }}" method="POST" class="flex flex-col gap-5">
            @csrf
            @method('PUT')

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Judul Task <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title"
                    value="{{ old('title', $todo->title) }}"
                    class="w-full px-4 py-2.5 rounded-xl border @error('title') border-red-400 bg-red-50 @else border-gray-200 @enderror text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                    autofocus>
                @error('title')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Description --}}
            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Deskripsi <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <textarea id="description" name="description" rows="3"
                    placeholder="Tambahkan catatan atau detail task..."
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('description', $todo->description) }}</textarea>
            </div>

            {{-- Category & Priority --}}
            <div class="grid grid-cols-2 gap-4">

                {{-- Category --}}
                <div>
                    <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1.5">Kategori</label>
                    <select id="category_id" name="category_id"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                        <option value="">Tanpa kategori</option>
                        @foreach($category as $cat)
                            <option value="{{ $cat->id }}"
                                {{ old('category_id', $todo->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->icon ? $cat->icon . ' ' : '' }}{{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Priority --}}
                <div>
                    <label for="priority" class="block text-sm font-medium text-gray-700 mb-1.5">
                        Priority <span class="text-red-500">*</span>
                    </label>
                    <select id="priority" name="priority"
                        class="w-full px-4 py-2.5 rounded-xl border @error('priority') border-red-400 bg-red-50 @else border-gray-200 @enderror text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                        <option value="low"    {{ old('priority', $todo->priority) === 'low'    ? 'selected' : '' }}>🟢 Low</option>
                        <option value="medium" {{ old('priority', $todo->priority) === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                        <option value="high"   {{ old('priority', $todo->priority) === 'high'   ? 'selected' : '' }}>🔴 High</option>
                    </select>
                    @error('priority')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Status --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
                <select id="status" name="status"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition bg-white">
                    <option value="pending"   {{ old('status', $todo->status) === 'pending'   ? 'selected' : '' }}>⏳ Pending</option>
                    <option value="active"    {{ old('status', $todo->status) === 'active'    ? 'selected' : '' }}>🔵 Active</option>
                    <option value="completed" {{ old('status', $todo->status) === 'completed' ? 'selected' : '' }}>✅ Completed</option>
                </select>
            </div>

            {{-- Deadline --}}
            <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Deadline <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="datetime-local" id="deadline" name="deadline"
                    value="{{ old('deadline', $todo->deadline ? $todo->deadline->copy()->setTimezone(auth()->user()->timezone ?? 'Asia/Jakarta')->format('Y-m-d\TH:i') : '') }}"
                    class="w-full px-4 py-2.5 rounded-xl border @error('deadline') border-red-400 bg-red-50 @else border-gray-200 @enderror text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                @error('deadline')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Buttons --}}
            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                    class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2.5 rounded-xl transition-colors flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Simpan Perubahan
                </button>
                <a href="{{ route('todo.index') }}"
                    class="px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-500 hover:bg-gray-50 transition-colors">
                    Batal
                </a>
            </div>

        </form>
    </div>
</div>
@endsection