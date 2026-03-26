<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PostureStressTestService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StressTestController extends Controller
{
    public function index()
    {
        Gate::authorize('access-admin');

        return Inertia::render('Admin/StressTest', [
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email']),
            'lastResult' => null,
            'httpPoolSize' => PostureStressTestService::HTTP_POOL_SIZE,
        ]);
    }

    public function store(Request $request, PostureStressTestService $stress)
    {
        Gate::authorize('access-admin');

        set_time_limit(300);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'count' => 'required|integer|min:1|max:10000',
            'mode' => 'required|in:direct,http_api',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $count = $validated['count'];
        $mode = $validated['mode'];

        if ($mode === 'http_api' && $count > 2000) {
            return Inertia::render('Admin/StressTest', [
                'users' => User::orderBy('name')
                    ->get(['id', 'name', 'email']),
                'lastResult' => null,
                'httpPoolSize' => PostureStressTestService::HTTP_POOL_SIZE,
            ])->withErrors([
                'count' => 'HTTP API mode is limited to 2,000 requests per run to avoid timeouts.',
            ]);
        }

        $result = $mode === 'direct'
            ? $stress->runDirect($user, $count)
            : $stress->runHttpApi($user, $count);

        $result['mode'] = $mode;
        $result['target_user_id'] = $user->id;
        $result['target_email'] = $user->email;

        return Inertia::render('Admin/StressTest', [
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email']),
            'lastResult' => $result,
            'httpPoolSize' => PostureStressTestService::HTTP_POOL_SIZE,
        ]);
    }
}
