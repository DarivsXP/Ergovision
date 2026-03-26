<?php

namespace App\Services;

use App\Support\StressCapacityMetrics;
use App\Support\StressTestHttpSsl;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiStressTestService
{
    /** Default concurrency for AI inference pools. */
    public const DEFAULT_CONCURRENCY = 10;

    /** Hard cap to keep admin UI safe. */
    public const MAX_CONCURRENCY = 50;

    /** Hard cap per run (admin UI). */
    public const MAX_REQUESTS = 3000;

    /** Heuristic: each active user triggers ~5 inferences / second (frontend is ~5fps). */
    public const ASSUMPTION_INFERENCES_PER_USER_PER_SECOND = 5.0;

    public function endpoint(): string
    {
        return rtrim((string) env('STRESS_AI_ENDPOINT', env('AI_MICROSERVICE_ENDPOINT', 'https://ergovision-ai.onrender.com/predict')));
    }

    /**
     * Concurrent POST inference calls (landmarks JSON, `/predict` contract).
     *
     * @return array<string, mixed>
     */
    public function run(int $totalRequests, int $concurrency): array
    {
        $endpoint = $this->endpoint();
        $totalRequests = max(1, min(self::MAX_REQUESTS, $totalRequests));
        $concurrency = max(1, min(self::MAX_CONCURRENCY, $concurrency));

        $success = 0;
        $failed = 0;
        $latenciesMs = [];
        $statusCounts = [];

        $payload = $this->samplePredictPayload();

        $t0 = microtime(true);
        $remaining = $totalRequests;

        while ($remaining > 0) {
            $batch = min($concurrency, $remaining);

            $responses = Http::pool(function (Pool $pool) use ($endpoint, $payload, $batch) {
                for ($i = 0; $i < $batch; $i++) {
                    $pending = $pool->as((string) $i)
                        ->acceptJson()
                        ->timeout(60)
                        ->connectTimeout(10);

                    if (StressTestHttpSsl::shouldRelax()) {
                        $pending = $pending->withoutVerifying();
                    }

                    $pending->post($endpoint, $payload);
                }
            });

            foreach ($responses as $response) {
                if ($response instanceof \Throwable) {
                    $failed++;
                    Log::warning('stress_ai_exception', ['message' => $response->getMessage()]);
                    continue;
                }

                $code = $response->status();
                $statusCounts[$code] = ($statusCounts[$code] ?? 0) + 1;

                if ($response->successful() && $this->looksLikePredictResponse($response->json())) {
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

        $estimatedConcurrentUsers = self::ASSUMPTION_INFERENCES_PER_USER_PER_SECOND > 0
            ? (int) floor($okPerS / self::ASSUMPTION_INFERENCES_PER_USER_PER_SECOND)
            : 0;

        return [
            'endpoint' => $endpoint,
            'wall_ms' => round($wallMs, 2),
            'total_requests' => $completed,
            'success' => $success,
            'failed' => $failed,
            'success_rate_pct' => $completed > 0 ? round(100 * $success / $completed, 2) : 0.0,
            'successful_req_per_s' => round($okPerS, 2),
            'latency_ms_avg' => round($avg, 2),
            'latency_ms_p95' => round($p95, 2),
            'concurrency' => $concurrency,
            'status_counts' => $statusCounts,
            'estimated_concurrent_active_users' => $estimatedConcurrentUsers,
            'assumption_inferences_per_user_per_second' => self::ASSUMPTION_INFERENCES_PER_USER_PER_SECOND,
            // Table-friendly status:
            'stable' => ($completed > 0) && (($success / $completed) >= 0.99),
        ];
    }

    private function looksLikePredictResponse($json): bool
    {
        if (! is_array($json)) {
            return false;
        }

        // Different deployments might return: {score, angles, label} or similar.
        return array_key_exists('score', $json) || array_key_exists('angles', $json) || array_key_exists('label', $json);
    }

    private function responseTimeMs(\Illuminate\Http\Client\Response $response): ?float
    {
        $stats = $response->handlerStats();
        $total = $stats['total_time'] ?? null;

        if ($total === null || ! is_numeric($total)) {
            return null;
        }

        return round((float) $total * 1000, 3);
    }

    private function samplePredictPayload(): array
    {
        // MediaPipe Pose typically yields 33 landmarks.
        $landmarks = [];
        for ($i = 0; $i < 33; $i++) {
            $landmarks[] = [
                'x' => 0.5,
                'y' => 0.5,
                'z' => 0.0,
                'visibility' => 0.99,
            ];
        }

        return [
            'landmarks' => $landmarks,
            'ideal_back' => 0,
            'ideal_neck' => 0,
            'is_calibrating' => false,
        ];
    }
}

