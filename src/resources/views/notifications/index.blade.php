@extends('layouts.app')

@section('title', 'Notifications - Wassup Task')
@section('page-title', 'Notifications')

@section('content')
<div x-data="{
    selected: [],
    allIds: {{ json_encode($notifications->pluck('id')) }},
    toggleAll() {
        if (this.selected.length === this.allIds.length) {
            this.selected = [];
        } else {
            this.selected = [...this.allIds];
        }
    },
    isAllSelected() {
        return this.allIds.length > 0 && this.selected.length === this.allIds.length;
    }
}" class="max-w-4xl mx-auto space-y-4">

    <x-modal-delete />

    {{-- Header Section --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2">
        <div>
            <h1 class="text-xl font-bold tracking-wider text-gray-900 uppercase">
                Notifications
            </h1>
            <p class="text-xs text-gray-400 mt-0.5">
                {{ $notifications->total() }} total notifikasi tercatat
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->unreadNotifications()->count() > 0)
            <form id="markAllReadForm" action="{{ route('notification.markAllRead') }}" method="POST" class="hidden">
                @csrf
            </form>
            <button type="button" onclick="document.getElementById('markAllReadForm').submit();"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-indigo-600 bg-white border border-indigo-200 rounded-lg hover:bg-indigo-50 transition-colors cursor-pointer shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                Mark all as read
            </button>
            @endif

            @if($notifications->total() > 0)
            <button type="button" onclick="openDeleteModal('{{ route('notification.destroyAll') }}', 'semua notifikasi')"
                title="Hapus Semua Notifikasi"
                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-500 bg-white border border-gray-200 rounded-lg hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition-colors cursor-pointer shadow-xs">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                Clear all
            </button>
            @endif
        </div>
    </div>

    {{-- Flash Message --}}
    @if(session('success'))
    <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs rounded-xl flex items-center justify-between">
        <div class="flex items-center gap-2">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    </div>
    @endif

    {{-- Selection Bar (Checkbox Pilih Semua & Tombol Hapus Terpilih) --}}
    @if($notifications->total() > 0)
    <div class="bg-white rounded-xl border border-gray-200/80 px-4 py-2.5 flex items-center justify-between gap-3 shadow-xs">
        <label class="flex items-center gap-2.5 text-xs font-semibold text-gray-700 cursor-pointer select-none">
            <input type="checkbox"
                   @change="toggleAll()"
                   :checked="isAllSelected()"
                   class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
            <span>Pilih Semua di Halaman Ini</span>
            <template x-if="selected.length > 0">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200/50"
                      x-text="selected.length + ' dipilih'"></span>
            </template>
        </label>

        <div>
            <template x-if="selected.length > 0">
                <button type="button"
                    @click="openBulkDeleteModal(selected)"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-rose-600 hover:bg-rose-700 rounded-lg transition-colors cursor-pointer shadow-xs active:scale-95">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <span>Hapus (<span x-text="selected.length"></span>) Terpilih</span>
                </button>
            </template>
        </div>
    </div>
    @endif

    {{-- Unified White Card List (Exact Style as Reference Image) --}}
    <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden divide-y divide-gray-100">

        @forelse($notifications as $notif)
        @php
            $data = $notif->data ?? [];
            $type = $data['type'] ?? '';
            $isUnread = is_null($notif->read_at);

            $isOverdue = $type === 'overdue' || $notif->type === \App\Notifications\TaskOverdue::class;
            $isUrgent  = $type === 'urgent' || $notif->type === \App\Notifications\TaskUrgentReminder::class;
            $isDigest  = $type === 'morning_digest' || $notif->type === \App\Notifications\MorningDigestNotification::class;
            $isDueToday = $type === 'due_today' || $notif->type === \App\Notifications\TaskDueTodayReminder::class;
            $isH3      = $type === 'h3_reminder' || $notif->type === \App\Notifications\TaskH3Reminder::class;

            // Badge Color & Label
            if ($isOverdue) {
                $badgeBg    = 'bg-red-500';
                $badgeText  = 'OVERDUE';
                $accentText = 'text-red-500';
                $subtitle   = 'Batas waktu tugas telah lewat. Segera periksa dan selesaikan tugas ini.';
            } elseif ($isUrgent) {
                $badgeBg    = 'bg-rose-500';
                $badgeText  = 'URGENT WARNING';
                $accentText = 'text-rose-500';
                $subtitle   = 'Waktu pengumpulan hampir habis! Pastikan tugasmu segera difinalisasi.';
            } elseif ($isDigest) {
                $badgeBg    = 'bg-emerald-500';
                $badgeText  = 'MORNING DIGEST';
                $accentText = 'text-emerald-600';
                $subtitle   = 'Rangkuman agenda tugas harian kamu untuk menjaga produktivitas hari ini.';
            } elseif ($isDueToday) {
                $badgeBg    = 'bg-sky-500';
                $badgeText  = 'DUE TODAY';
                $accentText = 'text-sky-600';
                $subtitle   = 'Tugas ini memiliki tenggat waktu hari ini. Jangan sampai terlewat!';
            } elseif ($isH3) {
                $badgeBg    = 'bg-purple-600';
                $badgeText  = 'EARLY WARNING';
                $accentText = 'text-purple-600';
                $subtitle   = 'Tugas ini jatuh tempo dalam 3 hari ke depan. Mulai cicil dari sekarang.';
            } else {
                $badgeBg    = 'bg-amber-500';
                $badgeText  = 'REMINDER';
                $accentText = 'text-amber-600';
                $subtitle   = 'Pengingat otomatis dari Wassup Task sebelum batas deadline tugas.';
            }

            $hasTask = isset($data['task_id']);
            $targetUrl = $hasTask ? route('notification.readAndRedirect', $notif->id) : null;
        @endphp

        <div class="p-5 md:p-6 flex items-start gap-3 sm:gap-4 transition-colors hover:bg-gray-50/60"
             :class="selected.includes('{{ $notif->id }}') ? 'bg-indigo-50/30' : '{{ $isUnread ? 'bg-indigo-50/15' : '' }}'">

            {{-- Checkbox per row --}}
            <div class="pt-1.5 shrink-0">
                <input type="checkbox"
                       value="{{ $notif->id }}"
                       x-model="selected"
                       class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500 cursor-pointer">
            </div>

            {{-- Square Dismiss/Delete Icon Button --}}
            <button type="button"
                onclick="openDeleteModal('{{ route('notification.destroy', $notif->id) }}', 'notifikasi ini')"
                title="Hapus notifikasi ini saja"
                class="w-7 h-7 sm:w-8 sm:h-8 rounded-lg bg-gray-100/90 text-gray-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center shrink-0 transition-colors cursor-pointer mt-0.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>

            {{-- Main Content Area --}}
            <div class="flex-1 min-w-0">

                {{-- Row 1: Solid Badge + Timestamp on the Right --}}
                <div class="flex items-center justify-between gap-2 flex-wrap">
                    <span class="inline-block {{ $badgeBg }} text-white text-[10px] sm:text-[11px] font-bold px-2.5 py-0.5 rounded-md tracking-wider">
                        {{ $badgeText }}
                    </span>

                    {{-- Timestamp with Clock Icon --}}
                    <div class="flex items-center gap-1.5 text-xs text-gray-400 font-normal">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ $notif->created_at->format('d M Y \a\t H:i') }}</span>
                    </div>
                </div>

                {{-- Row 2: Title / Main Message --}}
                <div class="mt-2">
                    @if($hasTask)
                        <a href="{{ $targetUrl }}"
                            class="text-sm sm:text-base font-bold text-gray-800 hover:text-indigo-600 transition-colors leading-snug block">
                            {{ $data['message'] ?? 'Pembaruan tugas baru' }}
                        </a>
                    @else
                        <h3 class="text-sm sm:text-base font-bold text-gray-800 leading-snug">
                            {{ $data['message'] ?? 'Notifikasi baru' }}
                        </h3>
                    @endif
                </div>

                {{-- Row 3: Subtitle / Description Text --}}
                <p class="text-xs sm:text-sm text-gray-500 mt-1 leading-relaxed">
                    {{ $subtitle }}
                </p>

                {{-- Row 4: Bottom Colored Accent Action --}}
                <div class="mt-2.5 flex items-center gap-3">
                    @if($hasTask)
                        <a href="{{ $targetUrl }}"
                            class="text-xs sm:text-sm font-semibold {{ $accentText }} hover:underline inline-flex items-center gap-1">
                            <span>Lihat Detail Tugas &rarr;</span>
                        </a>
                    @else
                        <span class="text-xs sm:text-sm font-semibold {{ $accentText }}">
                            Wassup Task Notification
                        </span>
                    @endif

                    @if($isUnread)
                        <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-600"></span>
                            Unread
                        </span>
                    @endif
                </div>

            </div>

        </div>

        @empty
        {{-- Empty State --}}
        <div class="py-16 px-4 text-center">
            <div class="w-12 h-12 rounded-2xl bg-gray-100 flex items-center justify-center mx-auto mb-3 text-gray-400">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
            <h3 class="text-sm font-bold text-gray-800">No Notifications</h3>
            <p class="text-xs text-gray-400 mt-1">Belum ada notifikasi baru saat ini.</p>
        </div>
        @endforelse

    </div>

    {{-- Pagination --}}
    @if($notifications->hasPages())
    <div class="pt-2">
        {{ $notifications->links() }}
    </div>
    @endif

</div>

@push('scripts')
<script>
    function openBulkDeleteModal(selectedIds) {
        if (!selectedIds || selectedIds.length === 0) return;
        const form = document.getElementById('deleteForm');
        form.action = '{{ route('notification.destroySelected') }}';
        form.querySelectorAll('.bulk-id-input').forEach(el => el.remove());
        selectedIds.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'ids[]';
            input.value = id;
            input.className = 'bulk-id-input';
            form.appendChild(input);
        });
        document.getElementById('modal-item-name').textContent = selectedIds.length + ' notifikasi terpilih';
        document.getElementById('deleteModal').classList.remove('hidden');
    }
</script>
@endpush
@endsection
