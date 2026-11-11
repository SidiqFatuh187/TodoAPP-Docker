<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Category;
use App\Models\Todo;

class DashboardController extends Controller
{
    public function index()
{
    $title    = 'Dashboard';
    $subtitle = 'Overview';

    $userId = Auth::id();

    $category = Category::where('user_id', $userId)
        ->withCount('todos')
        ->latest()
        ->get();

    $totalTasks     = Todo::where('user_id', $userId)
    ->count();

    $completedTasks = Todo::where('user_id', $userId)
    ->where('status', 'completed')
    ->count();

    $pendingTasks = Todo::where('user_id', $userId)
    ->whereIn('status', ['pending', 'active'])
    ->count();

    $activeTasks    = Todo::where('user_id', $userId)
    ->where('status', 'active')
    ->count();

    $highTasks      = Todo::where('user_id', $userId)->where('priority', 'high')->count();
    $mediumTasks    = Todo::where('user_id', $userId)->where('priority', 'medium')->count();
    $lowTasks       = Todo::where('user_id', $userId)->where('priority', 'low')->count();

    $recentTodos = Todo::where('user_id', $userId)
        ->with('category')
        ->latest()
        ->take(5)
        ->get();

   $overdueTasks = Todo::where('user_id', $userId)
            ->whereNotNull('deadline')
            ->where('deadline', '<', now('Asia/Jakarta'))
            ->whereIn('status', ['pending', 'active'])
            ->count();
            
    return view('dashboard', compact(
        'title', 'subtitle', 'category',
        'totalTasks', 'completedTasks', 'pendingTasks', 'activeTasks',
        'highTasks', 'mediumTasks', 'lowTasks', 'recentTodos','overdueTasks',
    ));
}
}
    