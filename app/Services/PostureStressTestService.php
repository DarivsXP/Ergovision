<?php

namespace App\Services;

use App\Models\PostureChunk;
use App\Models\User;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostureStressTestService
{
    /** Maximum rows per bulk insert batch (SQLite safety). */
    private const INSERT_CHUNK = 200;

    /** Concurrent HTTP requests per pool batch (document for reproducibility). */
    public const HTTP_POOL_SIZE = 20;

    /**
     * Bulk-insert posture chunks (database write throughput).
     *
     * @return array{duration_ms: float, count: int, throughput_per_s: float}
     */
    public function runDirect(User $user, int $count): array
    {
        $now = now();
        $rows = [];

        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'user_id' => $user->id,
                'score' => random_int(55, 100),
                'slouch_duration' => random_int(0, 20),
                'duration_seconds' => random_int(15, 120),
                'alert_count' => random_int(0, 5),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $t0 = microtime(true);

        foreach (array_chunk($rows, self::INSERT_CHUNK) as $chunk) {
            PostureChunk::insert($chunk);
        }

        $durationMs = (microtime(true) - $t0) * 1000;

        return [
            'duration_ms' => round($durationMs, 2),
            'count' => $count,
            'throughput_per_s' => $durationMs > 0 ? round($count / ($durationMs / 1000), 2) : 0.0,
        ];
    }

    /**
     * POST /api/posture-chunks with Sanctum (full stack: auth, validation, DB).
     *
     * @return array{duration_ms: float, count: int, success: int, failed: int, throughput_per_s: float}
     */
    public function runHttpApi(User $user, int $count): array
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $token = $user->createToken('stress-test')->plainTextToken;

        $payload = [
            'score' => 82,
            'slouch_duration' => 2,
            'duration_seconds' => 30,
            'alert_count' => 0,
        ];

        $success = 0;
        $failed = 0;
        $t0 = microtime(true);

        try {
            $remaining = $count;
            while ($remaining > 0) {
                $batch = min(self::HTTP_POOL_SIZE, $remaining);
                $responses = Http::pool(function (Pool $pool) use ($baseUrl, $token, $payload, $batch) {
                    for ($i = 0; $i < $batch; $i++) {
                        $pool->as((string) $i)
                            ->withToken($token)
                            ->acceptJson()
                            ->post("{$baseUrl}/api/posture-chunks", $payload);
                    }
                });

                foreach ($responses as $response) {
                    if ($response instanceof \Throwable) {
                        $failed++;
                        Log::warning('stress_test_http_exception', ['message' => $response->getMessage()]);

                        continue;
                    }
                    if ($response->successful()) {
                        $success++;
                    } else {
                        $failed++;
                        Log::warning('stress_test_http_failed', [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);
                    }
                }
                $remaining -= $batch;
            }
        } finally {
            $user->tokens()->where('name', 'stress-test')->delete();
        }

        $durationMs = (microtime(true) - $t0) * 1000;
        $total = $success + $failed;

        return [
            'duration_ms' => round($durationMs, 2),
            'count' => $total,
            'success' => $success,
            'failed' => $failed,
            'throughput_per_s' => $durationMs > 0 ? round($success / ($durationMs / 1000), 2) : 0.0,
        ];
    }
}
