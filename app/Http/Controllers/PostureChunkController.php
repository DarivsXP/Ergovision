<?php

namespace App\Http\Controllers;

use App\Models\PostureChunk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class PostureChunkController extends Controller
{
    public function index(Request $request)
    {
        // 1. Get Date (Default to Today in Philippines Time)
        $date = $request->input('date') ?: Carbon::now('Asia/Manila')->format('Y-m-d');

        // 2. Fetch Data for that specific date
        $chunks = PostureChunk::where('user_id', Auth::id())
            ->whereDate('created_at', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        // 3. Calculate Stats
        $totalChunks = $chunks->count();
        $averageScore = $totalChunks > 0 ? round($chunks->avg('score')) : 0;
        $totalAlerts = $chunks->sum('alert_count');
        
        // --- NEW: Calculate Total Duration ---
        // Sum the seconds from the database
        $totalSeconds = $chunks->sum('duration_seconds'); 
        
        // Format seconds into "1h 30m" or "45m"
        $dt = Carbon::now()->startOfDay()->addSeconds($totalSeconds);
        $totalDurationString = $totalSeconds >= 3600 
            ? $dt->format('H\h i\m') 
            : $dt->format('i\m s\s');

        // Calculate Slouch Time (Total seconds spent slouching)
        $totalSlouchSeconds = $chunks->sum('slouch_duration');

        return Inertia::render('Dashboard', [
            'postureChunks' => $chunks,
            'summaryStats' => [
                'averageScore' => $averageScore,
                'totalAlerts' => $totalAlerts,
                'totalSlouch' => $totalSlouchSeconds,
                'totalDuration' => $totalDurationString, // Passing the formatted string
                'totalLogs' => $totalChunks,
            ],
            'filters' => [
                'date' => $date
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
    
    public function destroySession($id)
    {
        // Direct lookup restricted to the logged-in user
        $chunk = PostureChunk::where('user_id', Auth::id())->find($id);

        if (!$chunk) {
            return redirect()->back()->with('error', 'Session not found.');
        }

        $chunk->delete();

        return redirect()->back()->with('message', 'Session removed from history.');
    }
    }