{{-- resources/views/components/plans-modal.blade.php --}}

<div id="plansModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[85vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <h3 class="text-base font-bold text-gray-800">Paket & Layanan (Plans)</h3>
            <button onclick="closePlansModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="overflow-y-auto px-6 py-5 flex-1 text-sm text-gray-500 leading-relaxed space-y-5">

            <div>
                <p class="font-semibold text-gray-700 mb-1">1. Layanan Gratis & Open Source</p>
                <p>Wassup Task dirancang sebagai aplikasi manajemen tugas yang dapat digunakan secara penuh tanpa biaya berlangganan. Seluruh fitur utama terbuka untuk semua pengguna terdaftar.</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">2. Fitur Tugas Standar</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Pembuatan dan pengelolaan tugas (CRUD) tanpa batas.</li>
                    <li>Kategori tugas kustom dengan pilihan warna dan ikon.</li>
                    <li>Unggah file lampiran tugas (PDF, Word, Excel, Foto) hingga 5 MB.</li>
                    <li>Pencarian tugas instan dan filter status (Pending, Aktif, Selesai).</li>
                    <li>Dukungan 3 zona waktu Indonesia (WIB, WITA, WIT).</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">3. Integrasi Pengingat WhatsApp</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Pengingat deadline otomatis ke nomor WhatsApp pribadi Anda setiap pagi.</li>
                    <li>Notifikasi H-1 (besok), hari H (hari ini), dan peringatan tugas telat (Overdue).</li>
                    <li>Sistem anti-spam agar pesan pengingat tidak terkirim ganda.</li>
                    <li>Pusat notifikasi di dalam aplikasi (in-app notification center).</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">4. Deployment Mandiri (Self-Hosted)</p>
                <p>Bagi developer atau tim yang ingin meng-host server sendiri, proyek ini menyediakan arsitektur Docker Compose siap pakai yang dapat dijalankan secara gratis dengan lisensi open source MIT.</p>
            </div>

            <p class="text-xs text-gray-400">© {{ date('Y') }} Wassup Task. Bebas digunakan untuk kebutuhan personal maupun tim.</p>

        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 shrink-0">
            <button onclick="closePlansModal()"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                Saya Mengerti
            </button>
        </div>

    </div>
</div>

<script>
    function openPlansModal() {
        document.getElementById('plansModal').classList.remove('hidden');
    }
    function closePlansModal() {
        document.getElementById('plansModal').classList.add('hidden');
    }
</script>
