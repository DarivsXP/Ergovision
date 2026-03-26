<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AiStressTestService;
use App\Services\PostureStressTestService;
use App\Services\SiteStressTestService;
use App\Support\StressCapacityMetrics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StressTestController extends Controller
{
    private function stressTestEnabled(): bool
    {
        return ! app()->environment('production')
            || filter_var(env('STRESS_TEST_ENABLED', false), FILTER_VALIDATE_BOOLEAN);
    }

    public function index()
    {
        Gate::authorize('access-admin');

        return Inertia::render('Admin/StressTest', [
            'users' => User::orderBy('name')
                ->get(['id', 'name', 'email']),
            'enabled' => $this->stressTestEnabled(),
            'limits' => [
                'telemetry_direct_batch' => PostureStressTestService::MAX_TELEMETRY_DIRECT_PER_REQUEST,
                'telemetry_http_batch' => PostureStressTestService::MAX_TELEMETRY_HTTP_PER_REQUEST,
                'site_max_requests' => 5000,
                'site_max_concurrency' => 50,
                'ai_max_requests' => AiStressTestService::MAX_REQUESTS,
                'ai_max_concurrency' => AiStressTestService::MAX_CONCURRENCY,
            ],
            'paths' => SiteStressTestService::DEFAULT_PATHS,
            'httpPoolSize' => PostureStressTestService::HTTP_POOL_SIZE,
            'ai' => [
                'endpoint' => (new AiStressTestService())->endpoint(),
                'assumption_inferences_per_user_per_second' => AiStressTestService::ASSUMPTION_INFERENCES_PER_USER_PER_SECOND,
            ],
        ]);
    }

    /**
     * Single batch (small) telemetry run — UI sends many batches to avoid 504 from proxies.
     */
    public function runTelemetryBatch(Request $request, PostureStressTestService $stress)
    {
        Gate::authorize('access-admin');
        if (! $this->stressTestEnabled()) {
            abort(404);
        }

        set_time_limit(120);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'count' => 'required|integer|min:1',
            'mode' => 'required|in:direct,http_api',
        ]);

        $max = $validated['mode'] === 'direct'
            ? PostureStressTestService::MAX_TELEMETRY_DIRECT_PER_REQUEST
            : PostureStressTestService::MAX_TELEMETRY_HTTP_PER_REQUEST;

        if ($validated['count'] > $max) {
            return response()->json([
                'message' => "Per request maximum is {$max} for this mode (use batched runs in the UI).",
            ], 422);
        }

        $user = User::findOrFail($validated['user_id']);
        $mode = $validated['mode'];

        $result = $mode === 'direct'
            ? $stress->runDirect($user, $validated['count'])
            : $stress->runHttpApi($user, $validated['count']);

        $result['mode'] = $mode;
        $result['target_user_id'] = $user->id;
        $result['target_email'] = $user->email;

        if ($mode === 'http_api') {
            $result['estimated_concurrent_active_users'] = StressCapacityMetrics::telemetryConcurrentUsers(
                (float) $result['throughput_per_s'],
                30.0
            );
            $result['assumption_seconds_between_posts_per_user'] = 30;
        } else {
            $result['estimated_concurrent_active_users'] = null;
            $result['capacity_note'] = 'Direct mode measures bulk DB insert throughput; it does not model concurrent HTTP clients.';
        }

        return response()->json(['ok' => true, 'batch' => $result]);
    }

    /**
     * Public page GET load (home, login, legal pages) with concurrency waves.
     */
    public function runSiteVisits(Request $request, SiteStressTestService $site)
    {
        Gate::authorize('access-admin');
        if (! $this->stressTestEnabled()) {
            abort(404);
        }

        set_time_limit(120);

        $validated = $request->validate([
            'total_requests' => 'required|integer|min:1|max:5000',
            'concurrency' => 'required|integer|min:1|max:50',
        ]);

        $baseUrl = rtrim((string) config('app.url'), '/');

        $result = $site->runPublicPageLoadTest(
            $baseUrl,
            $validated['total_requests'],
            $validated['concurrency']
        );

        return response()->json(['ok' => true, 'site' => $result]);
    }

    public function runAiInference(Request $request, AiStressTestService $ai)
    {
        Gate::authorize('access-admin');
        if (! $this->stressTestEnabled()) {
            abort(404);
        }

        set_time_limit(120);

        $validated = $request->validate([
            'total_requests' => 'required|integer|min:1|max:'.AiStressTestService::MAX_REQUESTS,
            'concurrency' => 'required|integer|min:1|max:'.AiStressTestService::MAX_CONCURRENCY,
        ]);

        $result = $ai->run($validated['total_requests'], $validated['concurrency']);

        return response()->json(['ok' => true, 'ai' => $result]);
    }
}
