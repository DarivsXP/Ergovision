<?php

namespace App\Services;

use App\Support\StressCapacityMetrics;
use App\Support\StressTestHttpSsl;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SiteStressTestService
{
    /** Hard cap per HTTP pool wave (avoid single request running too long). */
    public const MAX_REQUESTS_PER_WAVE = 50;

    /** Default public paths (no auth). */
    public const DEFAULT_PATHS = ['/', '/login', '/privacy-policy', '/terms-of-service'];

    /**
     * GET requests against public routes in waves of concurrent connections.
     *
     * @return array<string, mixed>
     */
    public function runPublicPageLoadTest(
        string $baseUrl,
        int $totalRequests,
        int $concurrency,
        ?array $paths = null,
    ): array {
        $baseUrl = rtrim($baseUrl, '/');
        $paths = $paths ?? self::DEFAULT_PATHS;
        $paths = array_values(array_filter($paths));

        if ($paths === []) {
            $paths = self::DEFAULT_PATHS;
        }

        $concurrency = max(1, min(50, $concurrency));
        $totalRequests = max(1, min(5000, $totalRequests));

        $latenciesMs = [];
        $success = 0;
        $failed = 0;
        $statusCounts = [];

        $t0 = microtime(true);
        $remaining = $totalRequests;
        $pathIndex = 0;

        while ($remaining > 0) {
            $batch = min(self::MAX_REQUESTS_PER_WAVE, $concurrency, $remaining);

            $responses = Http::pool(function (Pool $pool) use ($baseUrl, $paths, $batch, &$pathIndex) {
                for ($i = 0; $i < $batch; $i++) {
                    $path = $paths[$pathIndex % count($paths)];
                    $pathIndex++;
                    $url = $baseUrl.$path;
                    $pending = $pool->as((string) $i)
                        ->timeout(45)
                        ->connectTimeout(10);
                    if (StressTestHttpSsl::shouldRelax()) {
                        $pending = $pending->withoutVerifying();
                    }
                    $pending->get($url);
                }
            });

            foreach ($responses as $response) {
                if ($response instanceof \Throwable) {
                    $failed++;
                    Log::warning('stress_site_exception', ['message' => $response->getMessage()]);

                    continue;
                }

                $code = $response->status();
                $statusCounts[$code] = ($statusCounts[$code] ?? 0) + 1;

                if ($this->responseIndicatesReachablePage($response)) {
                    $success++;
                    $ms = $this->responseTimeMs($response);
                    if ($ms !== null) {
                        $latenciesMs[] = $ms;
                    }
                } else {
                    $failed++;
                }
            }

            $remaining -= $batch;
        }

        $wallMs = (microtime(true) - $t0) * 1000;
        $completed = $success + $failed;
        $okPerS = $wallMs > 0 ? $success / ($wallMs / 1000) : 0.0;

        sort($latenciesMs);
        $n = count($latenciesMs);
        $avg = $n > 0 ? array_sum($latenciesMs) / $n : 0.0;
        $p95 = $n > 0 ? $latenciesMs[(int) max(0, floor(0.95 * ($n - 1)))] : 0.0;

        return [
            'wall_ms' => round($wallMs, 2),
            'total_requests' => $completed,
            'success' => $success,
            'failed' => $failed,
            'success_rate_pct' => $completed > 0 ? round(100 * $success / $completed, 2) : 0.0,
            'req_per_s_observed' => round($completed / max($wallMs / 1000, 0.0001), 2),
            'successful_req_per_s' => round($okPerS, 2),
            'latency_ms_avg' => round($avg, 2),
            'latency_ms_p95' => round($p95, 2),
            'concurrency' => $concurrency,
            'paths' => $paths,
            'status_counts' => $statusCounts,
            'estimated_concurrent_visitors' => StressCapacityMetrics::browsingConcurrentUsers($okPerS, 2),
            'assumption_pages_per_user_per_minute' => 2,
        ];
    }

    private function responseIndicatesReachablePage(\Illuminate\Http\Client\Response $response): bool
    {
        $c = $response->status();

        return $c >= 200 && $c < 400;
    }

    private function responseTimeMs(\Illuminate\Http\Client\Response $response): ?float
    {
        $stats = $response->handlerStats();
        if (! is_array($stats)) {
            return null;
        }

        $total = $stats['total_time'] ?? null;
        if ($total === null && isset($stats['handler_stats']['total_time'])) {
            $total = $stats['handler_stats']['total_time'];
        }

        if ($total === null || ! is_numeric($total)) {
            return null;
        }

        return round((float) $total * 1000, 3);
    }
}
