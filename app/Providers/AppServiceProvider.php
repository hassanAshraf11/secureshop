<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        RateLimiter::for('identity', fn (Request $r) => [
            Limit::perMinute(25)->by('ip:'.$r->ip()),
            Limit::perMinute(5)->by('address:'.hash('sha256', strtolower(trim((string) $r->string('email'))))),
        ]);
        RateLimiter::for('factor', fn (Request $r) => Limit::perMinute(10)->by('factor:'.$r->ip()));
    }
}
