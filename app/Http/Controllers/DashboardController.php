<?php

namespace App\Http\Controllers;

use App\Models\PostureChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = PostureChunk::where('user_id', $user->id);

        // 1. Determine the Time Range
        $period = $request->input('period'); // 3d, 7d, 30d
        $specificDate = $request->input('date');

        if ($period) {
            $days = (int) filter_var($period, FILTER_SANITIZE_NUMBER_INT);
            $query->where('created_at', '>=', Carbon::now('Asia/Manila')->subDays($days));
        } elseif ($specificDate) {
            $query->whereDate('created_at', $specificDate);
        } else {
            // Default to last 7 days if nothing is selected
            $query->where('created_at', '>=', Carbon::now('Asia/Manila')->subDays(7));
            $period = '7d';
        }

        $chunks = $query->orderBy('created_at', 'asc')->get();

        // 2. Calculate Stats based on the filtered data
        $totalSeconds = $chunks->sum('duration_seconds');
        $totalSlouchSeconds = $chunks->sum('slouch_duration');
        
        // Format Duration (e.g., 2h 15m)
        $hours = floor($totalSeconds / 3600);
        $mins = floor(($totalSeconds % 3600) / 60);
        $durationString = $hours > 0 ? "{$hours}h {$mins}m" : "{$mins}m";

        return Inertia::render('Dashboard', [
            'postureChunks' => $chunks,
            'summaryStats' => [
                'averageScore' => $chunks->count() > 0 ? round($chunks->avg('score')) : 0,
                'totalAlerts' => $chunks->sum('alert_count'),
                'totalSlouch' => $totalSlouchSeconds,
                'totalDuration' => $durationString,
                'totalLogs' => $chunks->count(),
            ],
            'filters' => [
                'period' => $period,
                'date' => $specificDate
            ]
        ]);
    }
}