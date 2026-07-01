{{-- resources/views/components/terms-modal.blade.php --}}

<div id="termsModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[85vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <h3 class="text-base font-bold text-gray-800">Syarat & Ketentuan</h3>
            <button onclick="closeTermsModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="overflow-y-auto px-6 py-5 flex-1 text-sm text-gray-500 leading-relaxed space-y-5">

            <div>
                <p class="font-semibold text-gray-700 mb-1">1. Tentang Claro App</p>
                <p>Claro App adalah aplikasi manajemen tugas berbasis web yang membantu pengguna mengatur dan menyelesaikan tugas secara efisien, dilengkapi fitur pengingat deadline melalui WhatsApp.</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">2. Penerimaan Syarat</p>
                <p>Dengan mendaftar, Anda menyatakan telah membaca dan menyetujui seluruh syarat dan ketentuan ini.</p>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">3. Akun Pengguna</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Anda bertanggung jawab menjaga kerahasiaan password akun.</li>
                    <li>Satu email hanya untuk satu akun.</li>
                    <li>Informasi yang diberikan harus akurat dan valid.</li>
                    <li>Claro App berhak menonaktifkan akun yang melanggar ketentuan.</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">4. Notifikasi WhatsApp</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Fitur notifikasi WhatsApp bersifat opsional.</li>
                    <li>Nomor WA hanya digunakan untuk pengingat tugas, tidak dibagikan ke pihak ketiga.</li>
                    <li>Notifikasi dapat dinonaktifkan kapan saja di halaman Pengaturan.</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">5. Data & Privasi</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Data Anda disimpan dengan aman dan tidak dijual ke pihak ketiga.</li>
                    <li>Data tugas sepenuhnya milik Anda.</li>
                    <li>Anda dapat menghapus akun dan data kapan saja.</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">6. Batasan Layanan</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Claro App tidak bertanggung jawab atas kegagalan notifikasi akibat masalah jaringan.</li>
                    <li>Kami berhak mengubah layanan sewaktu-waktu dengan pemberitahuan.</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">7. Larangan Penggunaan</p>
                <ul class="list-disc list-inside space-y-1">
                    <li>Dilarang menggunakan Claro App untuk kegiatan ilegal.</li>
                    <li>Dilarang meretas atau mengganggu sistem.</li>
                    <li>Dilarang mendaftar dengan identitas palsu.</li>
                </ul>
            </div>

            <div>
                <p class="font-semibold text-gray-700 mb-1">8. Kontak</p>
                <p>Pertanyaan dapat disampaikan melalui <span class="text-indigo-600 font-medium">claro.my.id</span></p>
            </div>

            <p class="text-xs text-gray-400">© {{ date('Y') }} Claro App. Seluruh hak dilindungi.</p>

        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 shrink-0">
            <button onclick="closeTermsModal()"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold py-2.5 rounded-xl transition-colors">
                Saya Mengerti
            </button>
        </div>

    </div>
</div>

<script>
    function openTermsModal() {
        document.getElementById('termsModal').classList.remove('hidden');
    }
    function closeTermsModal() {
        document.getElementById('termsModal').classList.add('hidden');
    }
</script>