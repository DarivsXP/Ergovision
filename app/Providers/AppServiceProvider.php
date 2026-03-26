<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate; 
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
        Gate::define('access-admin', function (User $user) {
            return (bool) $user->is_admin;
        });

        // Keep stress tooling from overwhelming servers (especially behind proxies).
        RateLimiter::for('stress-test', function (Request $request) {
            $key = $request->user()?->id ? 'u:'.$request->user()->id : 'ip:'.$request->ip();

            return [
                Limit::perMinute(120)->by($key),
            ];
        });
    }
}