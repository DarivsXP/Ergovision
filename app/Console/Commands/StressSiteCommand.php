<?php

namespace App\Console\Commands;

use App\Services\SiteStressTestService;
use Illuminate\Console\Command;

class StressSiteCommand extends Command
{
    protected $signature = 'stress:site
                            {--requests=500 : Total GET requests to public pages}
                            {--concurrency=25 : Parallel requests per wave (1–50)}';

    protected $description = 'Load-test public routes (/, login, legal pages) for benchmarks / research.';

    public function handle(SiteStressTestService $site): int
    {
        $total = max(1, min(5000, (int) $this->option('requests')));
        $concurrency = max(1, min(50, (int) $this->option('concurrency')));
        $baseUrl = rtrim((string) config('app.url'), '/');

        $this->info("APP_URL: {$baseUrl}");
        $this->info("Requests: {$total}, concurrency per wave: {$concurrency}");

        $result = $site->runPublicPageLoadTest($baseUrl, $total, $concurrency);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Wall time (ms)', $result['wall_ms']],
                ['Success', $result['success']],
                ['Failed', $result['failed']],
                ['Success rate %', $result['success_rate_pct']],
                ['Successful req/s', $result['successful_req_per_s']],
                ['Latency avg (ms)', $result['latency_ms_avg']],
                ['Latency p95 (ms)', $result['latency_ms_p95']],
                ['Est. concurrent visitors (assumption: 2 pages/user/min)', $result['estimated_concurrent_visitors']],
            ]
        );

        $this->newLine();
        $this->comment('Document: hardware, PHP, web server, proxy timeouts, and APP_URL.');

        return self::SUCCESS;
    }
}
