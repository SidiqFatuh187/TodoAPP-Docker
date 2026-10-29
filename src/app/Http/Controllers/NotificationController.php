<?php

namespace App\Http\Controllers;

use App\Notifications\MorningDigestNotification;
use App\Notifications\TaskOverdue;
use App\Notifications\TaskUrgentReminder;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $filter = $request->query('filter', 'all');

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'overdue') {
            $query->where(function ($q) {
                $q->whereJsonContains('data->type', 'overdue')
                  ->orWhere('type', TaskOverdue::class);
            });
        } elseif ($filter === 'urgent') {
            $query->where(function ($q) {
                $q->whereJsonContains('data->type', 'urgent')
                  ->orWhere('type', TaskUrgentReminder::class);
            });
        } elseif ($filter === 'digest') {
            $query->where(function ($q) {
                $q->whereJsonContains('data->type', 'morning_digest')
                  ->orWhere('type', MorningDigestNotification::class);
            });
        }

        $notifications = $query->paginate(12)->withQueryString();

        // Summary counts
        $totalCount  = $user->notifications()->count();
        $unreadCount = $user->unreadNotifications()->count();
        $overdueCount = $user->notifications()->where(function ($q) {
            $q->whereJsonContains('data->type', 'overdue')
              ->orWhere('type', TaskOverdue::class);
        })->count();
        $urgentCount = $user->notifications()->where(function ($q) {
            $q->whereJsonContains('data->type', 'urgent')
              ->orWhere('type', TaskUrgentReminder::class);
        })->count();
        $digestCount = $user->notifications()->where(function ($q) {
            $q->whereJsonContains('data->type', 'morning_digest')
              ->orWhere('type', MorningDigestNotification::class);
        })->count();

        return view('notifications.index', compact(
            'notifications',
            'filter',
            'totalCount',
            'unreadCount',
            'overdueCount',
            'urgentCount',
            'digestCount'
        ));
    }

    public function markAllRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return redirect()->back()->with('success', 'Semua notifikasi berhasil ditandai telah dibaca.');
    }

    public function readAndRedirect(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        $taskId = $notification->data['task_id'] ?? null;
        if ($taskId) {
            return redirect()->route('todo.show', $taskId);
        }

        return redirect()->route('notifications.index');
    }

    public function destroy(string $id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return back()->with('success', 'Notifikasi berhasil dihapus.');
    }

    public function destroySelected(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array',
            'ids.*' => 'string',
        ]);

        $count = count($request->ids);
        auth()->user()->notifications()->whereIn('id', $request->ids)->delete();

        return back()->with('success', "{$count} notifikasi terpilih berhasil dihapus.");
    }

    public function destroyAllnotifications()
    {
        auth()->user()->notifications()->delete();

        return back()->with('success', 'Semua notifikasi berhasil dibersihkan.');
    }
}
