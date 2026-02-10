<?php

namespace App\Http\Controllers;

use App\Models\PostureChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PostureChunkController extends Controller
{
    /**
     * Dashboard view with Date filtering.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $todayPHT = Carbon::now('Asia/Manila')->toDateString();
        $selectedDate = $request->input('date', $todayPHT);

        $chunks = $user->postureChunks()
            ->whereDate('created_at', $selectedDate)
            ->latest()
            ->get();

        // Calculate Total Duration in Minutes for the Stat Card
        $totalSeconds = $chunks->sum('duration_seconds');
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $formattedDuration = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

        return Inertia::render('Dashboard', [
            'postureChunks' => $chunks,
            'filters' => ['date' => $selectedDate],
            'summaryStats' => [
                'averageScore' => $chunks->count() > 0 ? round($chunks->avg('score')) : 0,
                'totalAlerts'  => (int) $chunks->sum('alert_count'),
                'totalSlouch'  => (int) $chunks->sum('slouch_duration'), // In seconds
                'totalDuration'=> $formattedDuration, // NEW METRIC
                'totalLogs'    => $chunks->count(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'score'           => 'required|integer|min:0|max:100',
            'slouch_duration' => 'required|integer|min:0',
            'duration_seconds'=> 'required|integer|min:1', // NEW VALIDATION
            'alert_count'     => 'required|integer|min:0',  
        ]);

        $request->user()->postureChunks()->create($validated);

        return redirect()->back();
    }
    /**
     * Delete a specific entry.
     */
    public function destroy($id)
    {
        // 1. Direct lookup is faster for SQLite
        $chunk = PostureChunk::find($id);

        // 2. Security check
        if (!$chunk || $chunk->user_id !== Auth::id()) {
            return redirect()->back(303);
        }

        // 3. Delete
        $chunk->delete();

        // 4. Clean redirect
        return redirect()->back(303);
    }
    }