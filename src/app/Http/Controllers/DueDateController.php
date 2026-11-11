<?php

namespace App\Http\Controllers;

use App\Models\Todo;
use Illuminate\Support\Facades\Auth;

class DueDateController extends Controller
{
        public function index()
        {
             $userId = Auth::id();
            $timezone = Auth::user()->timezone ?? 'Asia/Jakarta';
            $localToday = now($timezone)->startOfDay();
            $todayStart = $localToday->copy()->setTimezone('Asia/Jakarta');
            $tomorrowStart = $localToday->copy()->addDay()->setTimezone('Asia/Jakarta');
            $laterStart = $localToday->copy()->addDays(2)->setTimezone('Asia/Jakarta');

            $overdue = Todo::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('deadline')
                ->where('deadline', '<', $todayStart)
                ->with('category')
                ->latest('deadline')
                ->get();

            $today = Todo::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('deadline')
                ->where('deadline', '>=', $todayStart)
                ->where('deadline', '<', $tomorrowStart)
                ->with('category')
                ->latest('deadline')
                ->get();

            $tomorrow = Todo::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('deadline')
                ->where('deadline', '>=', $tomorrowStart)
                ->where('deadline', '<', $laterStart)
                ->with('category')
                ->latest('deadline')
                ->get();
                
            $later = Todo::where('user_id', $userId)
                ->where('status', '!=', 'completed')
                ->whereNotNull('deadline')
                ->where('deadline', '>=', $laterStart)
                ->with('category')
                ->latest('deadline')
                ->get();

            return view('due-dates.index', compact('overdue', 'today', 'tomorrow','later'));
        
        }
}
