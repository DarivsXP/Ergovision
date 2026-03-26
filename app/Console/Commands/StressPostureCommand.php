<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PostureStressTestService;
use Illuminate\Console\Command;

class StressPostureCommand extends Command
{
    protected $signature = 'stress:posture
                            {--user= : Target user ID (required)}
                            {--count=1000 : Number of posture chunks to generate}
                            {--mode=direct : direct (bulk DB insert) or http_api (Sanctum + /api/posture-chunks)}';

    protected $description = 'Reproducible load test for posture telemetry (for benchmarks / research).';

    public function handle(PostureStressTestService $stress): int
    {
        $userId = $this->option('user');
        if ($userId === null || $userId === '') {
            $this->error('Specify --user=ID (target user that will receive test chunks).');

            return self::FAILURE;
        }

        $user = User::find($userId);
        if (! $user) {
            $this->error("User {$userId} not found.");

            return self::FAILURE;
        }

        $count = (int) $this->option('count');
        $mode = $this->option('mode');

        if (! in_array($mode, ['direct', 'http_api'], true)) {
            $this->error('Mode must be direct or http_api.');

            return self::FAILURE;
        }

        if ($mode === 'http_api' && $count > 2000) {
            $this->warn('Limiting count to 2000 for http_api mode.');
            $count = 2000;
        }

        $this->info("Target: {$user->email} (ID {$user->id})");
        $this->info("Mode: {$mode}, count: {$count}");

        if ($mode === 'direct') {
            $result = $stress->runDirect($user, $count);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Duration (ms)', $result['duration_ms']],
                    ['Rows', $result['count']],
                    ['Throughput (rows/s)', $result['throughput_per_s']],
                ]
            );
        } else {
            $result = $stress->runHttpApi($user, $count);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Duration (ms)', $result['duration_ms']],
                    ['Successful', $result['success']],
                    ['Failed', $result['failed']],
                    ['Throughput (ok/s)', $result['throughput_per_s']],
                ]
            );
        }

        $this->newLine();
        $this->comment('Document in your paper: hardware, PHP version, DB driver, APP_URL, and HTTP pool size ('.PostureStressTestService::HTTP_POOL_SIZE.').');

        return self::SUCCESS;
    }
}
