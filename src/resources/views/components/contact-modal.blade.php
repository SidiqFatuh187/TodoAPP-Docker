{{-- resources/views/components/contact-modal.blade.php --}}

<div id="contactModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
            <div>
                <h3 class="text-base font-bold text-gray-800">Hubungi Kami (Contact Us)</h3>
                <p class="text-xs text-gray-400 mt-0.5">Kami siap membantu kendala atau pertanyaan seputar Wassup Task</p>
            </div>
            <button onclick="closeContactModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Content --}}
        <div class="overflow-y-auto px-6 py-6 flex-1 text-sm leading-relaxed space-y-4">

            {{-- WhatsApp --}}
            <a href="https://wa.me/6285764694530?text=Halo%20Admin%20Wassup%20Task%2C%20saya%20butuh%20bantuan" 
               target="_blank" 
               class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-green-200 hover:bg-green-50/40 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.845L.057 23.143a.75.75 0 00.922.899l5.453-1.43A11.945 11.945 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.886 0-3.65-.52-5.157-1.424l-.36-.215-3.733.979.998-3.645-.234-.374A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 text-sm group-hover:text-green-700">Chat WhatsApp</p>
                    <p class="text-xs text-gray-400">Respons cepat untuk pertanyaan dan kendala reminder</p>
                </div>
                <span class="text-xs text-green-600 font-semibold group-hover:translate-x-0.5 transition-transform">Chat &rarr;</span>
            </a>

            {{-- GitHub --}}
            <a href="https://github.com/SidiqFatuh187/TodoAPP-Docker" 
               target="_blank" 
               class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-gray-300 hover:bg-gray-50 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-gray-900 text-white flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 text-sm group-hover:text-gray-900">GitHub Repository</p>
                    <p class="text-xs text-gray-400">Laporkan issues, bug report, atau kontribusi pull request</p>
                </div>
                <span class="text-xs text-gray-600 font-semibold group-hover:translate-x-0.5 transition-transform">Kunjungi &rarr;</span>
            </a>

            {{-- Email --}}
            <a href="mailto:support@wassuptask.my.id" 
               class="flex items-center gap-4 p-4 rounded-2xl border border-gray-100 hover:border-blue-200 hover:bg-blue-50/40 transition-all group">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-bold text-gray-800 text-sm group-hover:text-blue-700">Email Dukungan</p>
                    <p class="text-xs text-gray-400">Hubungi kami melalui email resmi aplikasi</p>
                </div>
                <span class="text-xs text-blue-600 font-semibold group-hover:translate-x-0.5 transition-transform">Kirim &rarr;</span>
            </a>

        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-gray-100 shrink-0 flex justify-end">
            <button onclick="closeContactModal()"
                class="w-full sm:w-auto bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold px-6 py-2.5 rounded-xl transition-colors">
                Tutup
            </button>
        </div>

    </div>
</div>

<script>
    function openContactModal() {
        document.getElementById('contactModal').classList.remove('hidden');
    }
    function closeContactModal() {
        document.getElementById('contactModal').classList.add('hidden');
    }
</script>

