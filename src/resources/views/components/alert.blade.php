@if(session('success'))
    <div class="mb-5 p-4 rounded-xl bg-green-50 border border-green-200 flex items-center gap-3">
        <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <polyline points="20 6 9 17 4 12"/>
        </svg>
        <p class="text-sm text-green-700">{{ session('success') }}</p>
    </div>
@endif

@if(session('error'))
    <div class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 flex items-center gap-3">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
        </svg>
        <p class="text-sm text-red-700">{{ session('error') }}</p>
    </div>
@endif