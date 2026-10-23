<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Routing\Controller;
use App\Models\User;
use App\Models\Todo;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use App\Models\ActivityLog;

class AdminController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'        => User::count(),
            'new_users_this_week'=> User::where('created_at', '>=', now()->startOfWeek())->count(),
            'total_tasks'        => Todo::count(),
            'completed_tasks'    => Todo::where('status', 'completed')->count(),
            'completion_rate'    => Todo::count() > 0
                                        ? round(Todo::where('status', 'completed')->count() / Todo::count() * 100)
                                        : 0,
            'overdue_tasks'      => Todo::where('status', '!=', 'completed')
                                        ->whereNotNull('deadline')
                                        ->where('deadline', '<', now())
                                        ->count(),
        ];

        $recent_users      = User::latest()->take(5)->get();
        $recent_activity = \App\Models\ActivityLog::with('user')->latest()->take(8)->get();

        return view('admin.index', compact('stats', 'recent_users', 'recent_activity'));
    }

    public function users(Request $request)
    {
        $users = User::when($request->search, fn($q) =>
                        $q->where('name', 'like', "%{$request->search}%")
                          ->orWhere('email', 'like', "%{$request->search}%")
                    )
                    ->withCount('todos')
                    ->latest()
                    ->paginate(15);

        return view('admin.users', compact('users'));
    }

    public function tasks(Request $request)
    {
        $tasks = Todo::with(['user', 'category'])
                    ->when($request->user_id, fn($q) => $q->where('user_id', $request->user_id))
                    ->when($request->status,  fn($q) => $q->where('status', $request->status))
                    ->latest()
                    ->paginate(20);

        $users = User::select('id', 'name')->get();

        return view('admin.tasks', compact('tasks', 'users'));
    }

    public function category()
    {
        $category= Category::with('user')
                        ->withCount('todos')
                        ->latest()
                        ->paginate(20);

        return view('admin.category', compact('category'));
    }

  public function activity()
    {
    $activity = \App\Models\ActivityLog::with('user')
        ->latest()
        ->paginate(20);
 
    return view('admin.activity', compact('activity'));
    }
 

    public function ban(User $user)
    {
        abort_if($user->id === auth()->id(), 403, 'Cannot ban yourself.');

        $user->update(['is_banned' => true]);

        return back()->with('success', "{$user->name} has been banned.");
    }

    public function unban(User $user)
    {
        $user->update(['is_banned' => false]);

        return back()->with('success', "{$user->name} has been unbanned.");
    }

    public function export()
    {
        $users = User::withCount('todos')->get();

        $csv = "ID,Name,Email,Role,Banned,Total Tasks,Joined\n";

        foreach ($users as $user) {
            $csv .= implode(',', [
                $user->id,
                "\"{$user->name}\"",
                $user->email,
                $user->role,
                $user->is_banned ? 'yes' : 'no',
                $user->todos_count,
                $user->created_at->format('Y-m-d'),
            ]) . "\n";
        }

        return Response::make($csv, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export_' . now()->format('Ymd') . '.csv"',
        ]);
    }

    public function destroyActivitylog(ActivityLog $activity)
    {
        $activity->delete();

        return back()->with('success', 'Activity log entry deleted successfully.');
    }

    public function destroyAllActivitylogs()
    {
        ActivityLog::truncate();

        return back()->with('success', 'All activity log entries deleted successfully.');
    }
}