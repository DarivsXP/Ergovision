<?php

namespace App\Support;

/**
 * Relax TLS verification for local stress runs (Laragon/Herd .test, self-signed, etc.).
 */
class StressTestHttpSsl
{
    public static function shouldRelax(): bool
    {
        if (filter_var(env('STRESS_TEST_INSECURE_SSL', false), FILTER_VALIDATE_BOOLEAN)) {
            return true;
        }

        if (in_array(app()->environment(), ['local', 'testing'], true)) {
            return true;
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        return str_ends_with($host, '.test')
            || str_contains($host, 'localhost')
            || str_contains($host, '127.0.0.1');
    }
}
