<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Todo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
class FonnteWebhookController extends Controller
{
    /**
     * Handle incoming Fonnte webhook request.
     */
    public function handle(Request $request): JsonResponse
    {
        // Handle verification ping from Fonnte (GET)
        if ($request->isMethod('get')) {
            return response()->json([
                'status'  => 'active',
                'service' => 'Wassup Task WhatsApp Webhook',
            ]);
        }

        // 1. Optional Secret Verification
        $secret = config('services.fonnte.webhook_secret');
        if ($secret) {
            $incomingSecret = $request->header('X-Webhook-Secret') 
                ?? $request->input('secret')
                ?? $request->query('secret');

            if ($incomingSecret !== $secret) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        }

        // 2. Extract sender and message
        Log::info('Fonnte incoming webhook payload:', $request->all());

        $rawSender = (string) ($request->input('sender') ?? $request->input('from') ?? '');
        $message   = trim((string) ($request->input('message') ?? ''));

        if (!$rawSender || $message === '') {
            return response()->json(['status' => 'ignored', 'reason' => 'empty_payload']);
        }

        // 3. Match user by phone
        $user = $this->findUserByPhone($rawSender);
        if (!$user) {
            $reply = "Halo! 👋 Nomor WhatsApp ini (*{$rawSender}*) belum terdaftar di *Wassup Task*.\n\n"
                   . "Silakan masukkan nomor ini di halaman Profil akun Wassup Task kamu untuk mengaktifkan fitur asisten WhatsApp.";
            $this->sendWhatsappReply($rawSender, $reply);
            return response()->json(['reply' => $reply]);
        }

        if ($user->is_banned) {
            $reply = 'Akun Anda di Wassup Task telah dinonaktifkan.';
            $this->sendWhatsappReply($rawSender, $reply);
            return response()->json(['reply' => $reply]);
        }

        // 4. Parse and process command
        $reply = $this->processMessage($user, $message);

        // Send response via Fonnte API
        $this->sendWhatsappReply($rawSender, $reply);

        return response()->json(['status' => 'success', 'reply' => $reply]);
    }

    /**
     * Parse and dispatch commands.
     */
    protected function processMessage(User $user, string $message): string
    {
        $upper = strtoupper(trim($message));
        $firstWord = strtoupper(strtok($upper, " \t\n\r") ?: '');

        return match ($firstWord) {
            'DONE', 'SELESAI' => $this->handleDone($user, $message),
            'TUNDA', 'SNOOZE' => $this->handleSnooze($user, $message),
            'TODO', 'BUAT'    => $this->handleQuickCreate($user, $message),
            'TUGAS', 'LIST'   => $this->handleList($user),
            'HELP', 'BANTUAN', 'MENU', 'INFO' => $this->handleHelp($user),
            default           => $this->handleFallback($user, $message),
        };
    }

    /**
     * Mark a task as completed: DONE <id>
     */
    protected function handleDone(User $user, string $message): string
    {
        if (!preg_match('/^(?:DONE|SELESAI)\s+#?(\d+)/i', trim($message), $matches)) {
            return "⚠️ Format salah. Ketik *DONE <ID_TUGAS>* (contoh: *DONE 42*).\n\nKetik *TUGAS* untuk melihat daftar ID tugas kamu.";
        }

        $taskId = (int) $matches[1];
        $todo = Todo::where('id', $taskId)
            ->where('user_id', $user->id)
            ->first();

        if (!$todo) {
            return "❌ Tugas dengan ID *#{$taskId}* tidak ditemukan atau bukan milik kamu.\nKetik *TUGAS* untuk cek daftar tugas aktif.";
        }

        if ($todo->status === 'completed') {
            return "ℹ️ Tugas *'{$todo->title}'* (ID: #{$taskId}) sudah berstatus SELESAI sebelumnya ✅";
        }

        $todo->update([
            'status'       => 'completed',
            'completed_at' => now(),
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'type'        => 'complete',
            'description' => "Menyelesaikan tugas via WhatsApp: {$todo->title}",
        ]);

        return "🎉 Mantap *{$user->name}*! Tugas *'{$todo->title}'* (ID: #{$taskId}) berhasil ditandai SELESAI ✅";
    }

    /**
     * Snooze / extend deadline: TUNDA <id> <durasi>
     * e.g. TUNDA 42 2h, TUNDA 42 1d, TUNDA 42 30m, TUNDA 42 2
     */
    protected function handleSnooze(User $user, string $message): string
    {
        if (!preg_match('/^(?:TUNDA|SNOOZE)\s+#?(\d+)(?:\s+(.+))?$/i', trim($message), $matches)) {
            return "⚠️ Format salah. Ketik *TUNDA <ID> <durasi>* (contoh: *TUNDA 42 2h* atau *TUNDA 42 1d*).";
        }

        $taskId   = (int) $matches[1];
        $duration = isset($matches[2]) ? trim($matches[2]) : '2h';

        $todo = Todo::where('id', $taskId)
            ->where('user_id', $user->id)
            ->first();

        if (!$todo) {
            return "❌ Tugas dengan ID *#{$taskId}* tidak ditemukan atau bukan milik kamu.";
        }

        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $baseTime = ($todo->deadline && Carbon::parse($todo->deadline)->isFuture())
            ? Carbon::parse($todo->deadline)
            : Carbon::now($timezone);

        $addedDesc = '';
        if (preg_match('/^(\d+)\s*(?:m|menit)$/i', $duration, $dMatch)) {
            $mins = (int) $dMatch[1];
            $newDeadline = $baseTime->copy()->addMinutes($mins);
            $addedDesc = "{$mins} menit";
        } elseif (preg_match('/^(\d+)\s*(?:d|hari|day|days)$/i', $duration, $dMatch)) {
            $days = (int) $dMatch[1];
            $newDeadline = $baseTime->copy()->addDays($days);
            $addedDesc = "{$days} hari";
        } elseif (preg_match('/^(\d+)\s*(?:h|jam|hour|hours)?$/i', $duration, $dMatch)) {
            $hours = (int) $dMatch[1];
            $newDeadline = $baseTime->copy()->addHours($hours);
            $addedDesc = "{$hours} jam";
        } else {
            // Default 2 jam jika parsing tidak dikenali
            $newDeadline = $baseTime->copy()->addHours(2);
            $addedDesc = "2 jam";
        }

        $todo->update([
            'deadline' => $newDeadline,
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'type'        => 'task_updated',
            'description' => "Memperpanjang deadline tugas via WhatsApp: {$todo->title}",
        ]);

        $formattedDeadline = $newDeadline->setTimezone($timezone)->translatedFormat('l, d M Y H:i');

        return "⏰ Siap *{$user->name}*! Deadline tugas *'{$todo->title}'* (ID: #{$taskId}) diundur {$addedDesc}.\n\n📅 Batas baru: *{$formattedDeadline}* ({$timezone})";
    }

    /**
     * Quick-create task: TODO <judul> [deadline opsional]
     */
    protected function handleQuickCreate(User $user, string $message): string
    {
        $raw = trim(preg_replace('/^(?:TODO|BUAT)\s+/i', '', trim($message)));
        if ($raw === '') {
            return "⚠️ Format salah. Ketik *TODO <Judul Tugas>* (contoh: *TODO Beli buku catatan*).";
        }

        // Cari atau buat kategori default
        $category = Category::where('user_id', $user->id)->first();
        if (!$category) {
            $category = Category::create([
                'user_id' => $user->id,
                'name'    => 'Umum',
                'color'   => '#6366f1',
                'icon'    => 'tasks',
            ]);
        }

        $todo = Todo::create([
            'user_id'     => $user->id,
            'category_id' => $category->id,
            'title'       => Str::limit($raw, 250),
            'status'      => 'pending',
            'priority'    => 'medium',
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'type'        => 'task_created',
            'description' => "Membuat tugas via WhatsApp: {$todo->title}",
        ]);

        return "✅ *Tugas baru berhasil dicatat!*\n\n"
             . "📌 *{$todo->title}* [ID: #{$todo->id}]\n"
             . "📁 Kategori: *{$category->name}*\n"
             . "🎯 Prioritas: *Sedang*\n\n"
             . "👉 Ketik *DONE {$todo->id}* jika sudah selesai!\n"
             . "👉 Ketik *TUNDA {$todo->id} 2h* untuk menambah deadline.";
    }

    /**
     * Show pending tasks: TUGAS / LIST
     */
    protected function handleList(User $user): string
    {
        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $todos = Todo::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'active'])
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END, deadline ASC')
            ->take(10)
            ->get();

        if ($todos->isEmpty()) {
            return "🎉 Hebat *{$user->name}*! Tidak ada tugas aktif saat ini. Semua tugasmu sudah selesai!\n\nKetik *TODO <judul>* jika ingin mencatat tugas baru.";
        }

        $message = "📋 *Daftar Tugas Aktif Kamu ({$todos->count()}):*\n\n";
        $i = 1;
        foreach ($todos as $todo) {
            $deadlineStr = $todo->deadline 
                ? Carbon::parse($todo->deadline)->setTimezone($timezone)->translatedFormat('d M H:i')
                : 'Tanpa deadline';

            $priorityIcon = match($todo->priority) {
                'high'   => '🔴',
                'medium' => '🟡',
                'low'    => '🟢',
                default  => '⚪',
            };

            $message .= "{$i}. {$priorityIcon} [ID: *#{$todo->id}*] *{$todo->title}*\n   📅 Deadline: {$deadlineStr}\n";
            $i++;
        }

        $message .= "\n👉 Balas *DONE <id>* untuk menyelesaikan tugas.\n"
                  . "👉 Balas *TUNDA <id> 2h* untuk mengundur deadline.";

        return $message;
    }

    /**
     * Show help guide.
     */
    protected function handleHelp(User $user): string
    {
        return "🤖 *Wassup Task — Asisten WhatsApp*\n\n"
             . "Halo *{$user->name}*! Berikut perintah cepat yang bisa kamu kirim:\n\n"
             . "✅ *DONE <id>* atau *SELESAI <id>*\n"
             . "Tandai tugas selesai (contoh: _DONE 42_)\n\n"
             . "⏰ *TUNDA <id> <durasi>*\n"
             . "Undur batas deadline (contoh: _TUNDA 42 2h_ atau _TUNDA 42 1d_)\n\n"
             . "📝 *TODO <judul>*\n"
             . "Buat tugas baru secara instan (contoh: _TODO Beli baterai AA_)\n\n"
             . "📋 *TUGAS* atau *LIST*\n"
             . "Lihat daftar 10 tugas aktif kamu beserta ID-nya\n\n"
             . "💡 *HELP*\n"
             . "Tampilkan panduan ini kembali.";
    }

    /**
     * Fallback response for unhandled message.
     */
    protected function handleFallback(User $user, string $message): string
    {
        return "Halo *{$user->name}*! 👋 Pesan kamu tidak dikenali.\n\n"
             . "Ketik *TUGAS* untuk melihat daftar tugas, atau ketik *HELP* untuk melihat format perintah yang tersedia.";
    }

    /**
     * Normalize and find user by phone number variants.
     */
    protected function findUserByPhone(string $rawPhone): ?User
    {
        $clean = preg_replace('/@.*$/', '', $rawPhone);
        $clean = preg_replace('/\D/', '', $clean);

        if (empty($clean)) {
            return null;
        }

        $variants = [$clean];
        if (str_starts_with($clean, '62')) {
            $variants[] = '0' . substr($clean, 2);
            $variants[] = '+' . $clean;
        } elseif (str_starts_with($clean, '0')) {
            $variants[] = '62' . substr($clean, 1);
            $variants[] = '+62' . substr($clean, 1);
        }

        return User::whereIn('phone', array_unique($variants))->orderBy('id', 'desc')->first();
    }

    /**
     * Send WhatsApp message back to sender using Fonnte API.
     */
    protected function sendWhatsappReply(string $target, string $message): void
    {
        $token = config('services.fonnte.token');
        if (!$token) {
            Log::warning('FONNTE_TOKEN is not configured, skipping WhatsApp reply.');
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $token,
            ])->post(config('services.fonnte.url', 'https://api.fonnte.com/send'), [
                'target'  => $target,
                'message' => $message,
            ]);

            Log::info("Fonnte reply dispatched to {$target}:", [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Fonnte reply failed to send: ' . $e->getMessage());
        }
    }
}
