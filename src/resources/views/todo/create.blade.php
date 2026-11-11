@extends('layouts.app')

@section('title', 'Tambah Task')

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

        <form action="{{ route('todo.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5">
            @csrf

            {{-- Title --}}
            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Judul Task <span class="text-red-500">*</span>
                </label>
                <input type="text" id="title" name="title" value="{{ old('title') }}"
                    placeholder="Contoh: Belajar Laravel, Beli sayuran..."
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
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition resize-none">{{ old('description') }}</textarea>
            </div>

            {{-- File Upload --}}
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Lampiran <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="file" id="file" name="file"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-blue-50 file:text-blue-600 hover:file:bg-blue-100">
                <p class="mt-1.5 text-xs text-gray-400">PDF, Word, Excel, atau gambar — maks. 5MB</p>

                {{-- Tampilkan file existing di edit --}}
                @if(isset($todo) && $todo->file_path)
                    <div class="mt-2 flex items-center gap-2 p-2.5 bg-gray-50 rounded-xl border border-gray-100">
                        <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                        </svg>
                        <span class="text-xs text-gray-600 truncate flex-1">{{ $todo->file_name }}</span>
                        <a href="{{ route('todo.download', $todo->id) }}" target="_blank"
                            class="text-xs text-blue-600 hover:text-blue-700 shrink-0">Lihat</a>
                    </div>
                    <p class="mt-1 text-xs text-gray-400">Upload file baru untuk mengganti yang lama</p>
                @endif
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
                            <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
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
                        <option value="low"    {{ old('priority') === 'low'    ? 'selected' : '' }}>🟢 Low</option>
                        <option value="medium" {{ old('priority', 'medium') === 'medium' ? 'selected' : '' }}>🟡 Medium</option>
                        <option value="high"   {{ old('priority') === 'high'   ? 'selected' : '' }}>🔴 High</option>
                    </select>
                    @error('priority')
                        <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            {{-- Deadline --}}
            <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Deadline <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <input type="datetime-local" id="deadline" name="deadline"
                    value="{{ old('deadline') }}"
                    min="{{ now(auth()->user()->timezone ?? 'Asia/Jakarta')->format('Y-m-d\TH:i') }}"
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
                    Simpan Task
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