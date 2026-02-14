<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PostureChunk;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    /**
     * Display the global admin dashboard.
     */
    public function index()
    {
        // Strict Gate Check
        if (!Gate::allows('access-admin')) {
        abort(403, 'You are not an admin. Your status is: ' . (auth()->user()->is_admin ? 'True' : 'False'));
    }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'active_sessions' => PostureChunk::where('created_at', '>', now()->subHours(24))->count(),
                'avg_score' => round(PostureChunk::avg('score') ?? 100, 1),
            ],
            'recent_users' => User::where('is_admin', false)
                ->latest()
                ->take(5)
                ->get(),
            'chart_data' => $this->getWeeklyTrends()
        ]);
    }

    /**
     * Display detailed stats for a specific user.
     */
    public function show(User $user)
    {
        if (!Gate::allows('access-admin')) {
            abort(403);
        }

        // Get the relationship query
        $chunksQuery = $user->postureChunks();

        return Inertia::render('Admin/UserDetail', [
            'user' => $user,
            'sessions' => $chunksQuery->latest()->paginate(10),
            'stats' => [
                'avg_score' => round($chunksQuery->avg('score') ?? 0, 1),
                'total_slouch_time' => (int) $chunksQuery->sum('slouch_duration'),
                'total_sessions' => $chunksQuery->count(),
            ]
        ]);
    }

    /**
     * Helper to get posture trends for the line chart.
     */
    private function getWeeklyTrends()
    {
        return PostureChunk::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('ROUND(AVG(score), 1) as avg_score')
        )
        ->where('created_at', '>', now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date', 'ASC')
        ->get();
    }
}