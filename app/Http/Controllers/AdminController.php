<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\PostureChunk;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminController extends Controller
{
    /**
     * Display the global admin dashboard.
     */
    public function index()
    {
        if (!Gate::allows('access-admin')) {
            abort(403);
        }

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_users' => User::count(),
                'active_sessions' => PostureChunk::where('created_at', '>', now()->subHours(24))->count(),
                'avg_score' => round(PostureChunk::avg('score') ?? 100, 1),
            ],
            // Dashboard only shows the top 5 recent users
            'recent_users' => User::where('is_admin', false)
                ->latest()
                ->take(5)
                ->get(),
            'chart_data' => $this->getWeeklyTrends()
        ]);
    }

    /**
     * ---------------------------------------------------------
     * NEW METHOD: Display the full list of users with SEARCH.
     * ---------------------------------------------------------
     */
    public function users(Request $request)
    {
        if (!Gate::allows('access-admin')) {
            abort(403);
        }

        // 1. Start the query
        $query = User::latest();

        // 2. Apply Search Filter if it exists
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 3. Fetch results (keep search params in pagination links)
        $users = $query->paginate(10)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search']), // Send search term back to frontend
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

    // Show the Edit Form
    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', [
            'userToEdit' => $user
        ]);
    }

    // Handle the Update Request
    public function update(Request $request, User $user)
    {
        // 1. Validate
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id, 
            'is_admin' => 'required|boolean',
        ]);

        // 2. Update
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'is_admin' => $validated['is_admin'],
        ]);

        // 3. Redirect using 303 to prevent PATCH errors
        return to_route('admin.users.index', [], 303)
            ->with('message', 'User updated successfully!');
    }

    public function destroy(User $user)
    {
        // Optional: Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own admin account.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('message', 'User deleted successfully.');
    }
}