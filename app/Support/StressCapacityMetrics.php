<?php

namespace App\Support;

/**
 * Heuristic capacity figures for research reporting (document assumptions in your paper).
 */
class StressCapacityMetrics
{
    /**
     * If each active user submits one telemetry chunk every $intervalSeconds on average,
     * approximate how many simultaneous "active posture" users the observed POST rate supports.
     */
    public static function telemetryConcurrentUsers(float $successfulPostsPerSecond, float $intervalSeconds = 30): int
    {
        if ($successfulPostsPerSecond <= 0 || $intervalSeconds <= 0) {
            return 0;
        }

        return (int) floor($successfulPostsPerSecond * $intervalSeconds);
    }

    /**
     * If each browsing user loads about $pagesPerUserPerMinute public pages while navigating,
     * approximate how many concurrent visitors the observed page-request rate supports.
     */
    public static function browsingConcurrentUsers(float $successfulReqPerSecond, float $pagesPerUserPerMinute = 2): int
    {
        if ($successfulReqPerSecond <= 0 || $pagesPerUserPerMinute <= 0) {
            return 0;
        }

        return (int) floor(($successfulReqPerSecond * 60) / $pagesPerUserPerMinute);
    }
}
