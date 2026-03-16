<?php

namespace App\Http\Controllers;

use App\Models\PostureChunk;
use App\Models\SessionFeedback;
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

        // 3. Compute trend metrics (3-day, 7-day, 30-day averages, independent of current filter)
        $now = Carbon::now('Asia/Manila');

        $last3DaysChunks = PostureChunk::where('user_id', $user->id)
            ->where('created_at', '>=', (clone $now)->subDays(3))
            ->get();

        $last7DaysChunks = PostureChunk::where('user_id', $user->id)
            ->where('created_at', '>=', (clone $now)->subDays(7))
            ->get();

        $last30DaysChunks = PostureChunk::where('user_id', $user->id)
            ->where('created_at', '>=', (clone $now)->subDays(30))
            ->get();

        $avgLast3 = $last3DaysChunks->count() > 0 ? round($last3DaysChunks->avg('score')) : null;
        $avgLast7 = $last7DaysChunks->count() > 0 ? round($last7DaysChunks->avg('score')) : null;
        $avgLast30 = $last30DaysChunks->count() > 0 ? round($last30DaysChunks->avg('score')) : null;

        $delta3vs7 = null;
        if (!is_null($avgLast3) && !is_null($avgLast7) && $avgLast7 > 0) {
            $delta3vs7 = round($avgLast3 - $avgLast7, 1);
        }

        // 4. Fetch recent feedback to annotate history rows
        $feedbackForDashboard = SessionFeedback::query()
            ->where('user_id', $user->id)
            ->latest()
            ->take(100)
            ->get([
                'id',
                'fatigue_level',
                'accuracy_rating',
                'comments',
                'created_at',
            ]);

        return Inertia::render('Dashboard', [
            'postureChunks' => $chunks,
            'summaryStats' => [
                'averageScore' => $chunks->count() > 0 ? round($chunks->avg('score')) : 0,
                'totalAlerts' => $chunks->sum('alert_count'),
                'totalSlouch' => $totalSlouchSeconds,
                'totalDuration' => $durationString,
                'totalLogs' => $chunks->count(),
                'trend' => [
                    'last3' => $avgLast3,
                    'last7' => $avgLast7,
                    'last30' => $avgLast30,
                    'delta3vs7' => $delta3vs7,
                ],
            ],
            'filters' => [
                'period' => $period,
                'date' => $specificDate
            ],
            'feedbackForDashboard' => $feedbackForDashboard,
        ]);
    }
}