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
     * Quick-create task: TODO <judul> [deadline opsional] [prioritas opsional]
     */
    protected function handleQuickCreate(User $user, string $message): string
    {
        $raw = trim(preg_replace('/^(?:TODO|BUAT)\s+/i', '', trim($message)));
        if ($raw === '') {
            return "⚠️ Format salah. Ketik *TODO <Judul Tugas>* (contoh: *TODO Sarapan Pagi | besok 08:00*).";
        }

        $timezone = $user->timezone ?? 'Asia/Jakarta';
        $parsed   = $this->parseTaskInput($raw, $timezone);

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
            'title'       => Str::limit($parsed['title'], 250),
            'status'      => 'pending',
            'priority'    => $parsed['priority'],
            'deadline'    => $parsed['deadline'],
        ]);

        ActivityLog::create([
            'user_id'     => $user->id,
            'type'        => 'task_created',
            'description' => "Membuat tugas via WhatsApp: {$todo->title}",
        ]);

        $priorityBadge = match($todo->priority) {
            'high'   => '🔴 *Tinggi*',
            'low'    => '🟢 *Rendah*',
            default  => '🟡 *Sedang*',
        };

        $reply = "✅ *Tugas baru berhasil dicatat!*\n\n"
               . "📌 *{$todo->title}* [ID: #{$todo->id}]\n"
               . "📁 Kategori: *{$category->name}*\n"
               . "🎯 Prioritas: {$priorityBadge}\n";

        if ($todo->deadline) {
            $deadlineStr = Carbon::parse($todo->deadline)->setTimezone($timezone)->translatedFormat('l, d M Y — H:i');
            $reply .= "⏰ Deadline: *{$deadlineStr}* ({$timezone})\n\n"
                    . "🔔 *Pengingat Otomatis Aktif:*\n"
                    . "Kamu akan menerima notifikasi WA otomatis menjelang batas deadline.\n\n";
        } else {
            $reply .= "⏰ Deadline: _Tanpa deadline_\n\n"
                    . "💡 *Tips:* Kamu bisa ketik *TUNDA {$todo->id} 2h* untuk menambah batas waktu.\n\n";
        }

        $reply .= "👉 Ketik *DONE {$todo->id}* jika sudah selesai!";

        return $reply;
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
                ? Carbon::parse($todo->deadline)->setTimezone($timezone)->translatedFormat('d M Y, H:i')
                : 'Tanpa deadline';

            $priorityBadge = match($todo->priority) {
                'high'   => '🔴 Tinggi',
                'low'    => '🟢 Rendah',
                default  => '🟡 Sedang',
            };

            $message .= "{$i}. [ID: *#{$todo->id}*] *{$todo->title}*\n"
                      . "   ⏰ Deadline: *{$deadlineStr}*\n"
                      . "   🎯 Prioritas: {$priorityBadge}\n";
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
             . "Halo *{$user->name}*! Berikut format perintah yang bisa kamu kirim:\n\n"
             . "📝 *Buat Tugas Baru:*\n"
             . "• *TODO <Judul> | <Deadline> | <Prioritas>*\n"
             . "  Contoh: _TODO Sarapan Pagi | besok 08:00 | tinggi_\n"
             . "  Contoh: _TODO Laporan Praktikum besok jam 5 sore penting_\n\n"
             . "✅ *Selesaikan Tugas:*\n"
             . "• *DONE <id>* atau *SELESAI <id>*\n"
             . "  Contoh: _DONE 31_\n\n"
             . "⏰ *Undur Deadline:*\n"
             . "• *TUNDA <id> <durasi>*\n"
             . "  Contoh: _TUNDA 31 2h_ atau _TUNDA 31 1d_\n\n"
             . "📋 *Daftar Tugas Aktif:*\n"
             . "• *TUGAS* atau *LIST*\n\n"
             . "💡 *HELP*\n"
             . "Tampilkan panduan ini.";
    }

    /**
     * Parse title, deadline, and priority from raw input text.
     *
     * @return array{title: string, deadline: ?Carbon, priority: string}
     */
    protected function parseTaskInput(string $rawInput, string $timezone): array
    {
        $priority = 'medium';
        $deadline = null;
        $title    = $rawInput;

        // 1. Explicit Separator: Title | Deadline | Priority
        if (str_contains($rawInput, '|')) {
            $parts = array_map('trim', explode('|', $rawInput));
            $title = $parts[0];

            if (isset($parts[1]) && $parts[1] !== '') {
                $deadline = $this->parseDateTimeString($parts[1], $timezone);
            }

            if (isset($parts[2]) && $parts[2] !== '') {
                $priority = $this->parsePriorityString($parts[2]);
            }

            return [
                'title'    => $title ?: $rawInput,
                'deadline' => $deadline,
                'priority' => $priority,
            ];
        }

        // 2. Keyword-based Priority
        if (preg_match('/\b(urgent|darurat|penting|tinggi|high)\b/i', $title)) {
            $priority = 'high';
            $title = trim(preg_replace('/\b(urgent|darurat|penting|tinggi|high)\b/i', '', $title));
        } elseif (preg_match('/\b(santai|rendah|low)\b/i', $title)) {
            $priority = 'low';
            $title = trim(preg_replace('/\b(santai|rendah|low)\b/i', '', $title));
        }

        // 3. Natural Language Deadline Parser
        $now = Carbon::now($timezone);
        $dateBase = null;
        $dayFound = false;

        // A. Indonesian Day / Period of Day matching
        if (preg_match('/\b(besok\s+(?:pagi|siang|sore|malam)|besok)\b/i', $title, $m)) {
            $dateBase = $now->copy()->addDay();
            $dayFound = true;
            $title = trim(preg_replace('/\b' . preg_quote($m[0], '/') . '\b/i', '', $title));
        } elseif (preg_match('/\b(lusa\s+(?:pagi|siang|sore|malam)|lusa)\b/i', $title, $m)) {
            $dateBase = $now->copy()->addDays(2);
            $dayFound = true;
            $title = trim(preg_replace('/\b' . preg_quote($m[0], '/') . '\b/i', '', $title));
        } elseif (preg_match('/\b(hari ini|nanti\s+(?:pagi|siang|sore|malam)|sore ini|malam ini|siang ini|pagi ini|nanti)\b/i', $title, $m)) {
            $dateBase = $now->copy();
            $dayFound = true;
            $title = trim(preg_replace('/\b' . preg_quote($m[0], '/') . '\b/i', '', $title));
        }

        // B. Time matching (HH:MM or HH.MM like 15:00, 15.00, jam 15:00, pukul 15.00)
        $timeFound = false;
        $hour = 17;
        $minute = 0;

        if (preg_match('/(?:jam|pukul)?\s*(\d{1,2})[:.](\d{2})(?:\s*(?:wib|wita|wit))?/i', $title, $tm)) {
            $h = (int) $tm[1];
            $min = (int) $tm[2];
            if ($h >= 0 && $h <= 23 && $min >= 0 && $min <= 59) {
                $hour = $h;
                $minute = $min;
                $timeFound = true;
                $title = trim(preg_replace('/' . preg_quote($tm[0], '/') . '/i', '', $title));
            }
        } elseif (preg_match('/(?:jam|pukul)\s*(\d{1,2})\s*(pagi|siang|sore|malam)?/i', $title, $tm)) {
            $h = (int) $tm[1];
            $period = strtolower($tm[2] ?? '');

            if (($period === 'sore' || $period === 'malam') && $h < 12) {
                $h += 12;
            } elseif ($period === 'siang' && $h >= 1 && $h < 11) {
                $h += 12;
            }

            if ($h >= 0 && $h <= 23) {
                $hour = $h;
                $minute = 0;
                $timeFound = true;
                $title = trim(preg_replace('/' . preg_quote($tm[0], '/') . '/i', '', $title));
            }
        } elseif (preg_match('/\b(pagi|siang|sore|malam)\b/i', $title, $pm)) {
            $period = strtolower($pm[1]);
            $hour = match($period) {
                'pagi'  => 8,
                'siang' => 12,
                'sore'  => 17,
                'malam' => 20,
            };
            $minute = 0;
            $timeFound = true;
            $title = trim(preg_replace('/\b' . preg_quote($pm[0], '/') . '\b/i', '', $title));
        }

        if ($dayFound || $timeFound) {
            $base = $dateBase ?? $now->copy();
            if (!$dayFound && $timeFound) {
                $targetTime = $base->copy()->setTime($hour, $minute, 0);
                if ($targetTime->isPast()) {
                    $base->addDay();
                }
            }
            $deadline = $base->setTime($hour, $minute, 0);
        }

        // Clean common Indonesian filler words
        $title = trim(preg_replace('/^(?:aku|akuu|saya|tolong|ingatin|ingatkan|mau|mauu|pengen|jadwalkan|jadwal)\s+/i', '', $title));
        $title = trim(preg_replace('/^(?:aku|akuu|saya|mau|mauu)\s+/i', '', $title));
        $title = trim(preg_replace('/\s+/', ' ', $title));
        $title = ucfirst($title);

        return [
            'title'    => $title ?: $rawInput,
            'deadline' => $deadline,
            'priority' => $priority,
        ];
    }

    protected function parseDateTimeString(string $str, string $timezone): ?Carbon
    {
        $clean = trim(strtolower($str));
        $now = Carbon::now($timezone);

        if (str_starts_with($clean, 'hari ini') || str_starts_with($clean, 'today')) {
            $timePart = trim(preg_replace('/^(?:hari ini|today)\s*(?:jam)?\s*/i', '', $clean));
            return $this->applyTimeToBase($now->copy(), $timePart);
        }

        if (str_starts_with($clean, 'besok') || str_starts_with($clean, 'tomorrow')) {
            $timePart = trim(preg_replace('/^(?:besok|tomorrow)\s*(?:jam)?\s*/i', '', $clean));
            return $this->applyTimeToBase($now->copy()->addDay(), $timePart);
        }

        if (str_starts_with($clean, 'lusa')) {
            $timePart = trim(preg_replace('/^lusa\s*(?:jam)?\s*/i', '', $clean));
            return $this->applyTimeToBase($now->copy()->addDays(2), $timePart);
        }

        try {
            return Carbon::parse($str, $timezone);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function applyTimeToBase(Carbon $base, string $timePart): Carbon
    {
        // Support HH:MM or HH.MM (e.g. 15:00 or 15.00)
        if (preg_match('/(\d{1,2})[:.](\d{2})/i', $timePart, $m)) {
            $h = (int) $m[1];
            $min = (int) $m[2];
            return $base->setTime($h, $min, 0);
        }

        if (preg_match('/(?:jam|pukul)?\s*(\d{1,2})\s*(pagi|siang|sore|malam)?/i', $timePart, $m)) {
            $h = (int) $m[1];
            $period = strtolower($m[2] ?? '');

            if (($period === 'sore' || $period === 'malam') && $h < 12) {
                $h += 12;
            } elseif ($period === 'siang' && $h >= 1 && $h < 11) {
                $h += 12;
            }

            return $base->setTime($h, 0, 0);
        }

        if (preg_match('/\b(pagi)\b/i', $timePart)) {
            return $base->setTime(8, 0, 0);
        }
        if (preg_match('/\b(siang)\b/i', $timePart)) {
            return $base->setTime(12, 0, 0);
        }
        if (preg_match('/\b(sore)\b/i', $timePart)) {
            return $base->setTime(17, 0, 0);
        }
        if (preg_match('/\b(malam)\b/i', $timePart)) {
            return $base->setTime(20, 0, 0);
        }

        return $base->setTime(17, 0, 0);
    }

    protected function parsePriorityString(string $str): string
    {
        $clean = strtolower(trim($str));
        if (in_array($clean, ['high', 'tinggi', 'penting', 'urgent', 'darurat'], true)) {
            return 'high';
        }
        if (in_array($clean, ['low', 'rendah', 'santai'], true)) {
            return 'low';
        }
        return 'medium';
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
