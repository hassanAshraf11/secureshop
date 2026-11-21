<?php

use App\Http\Middleware\BrowserHeaders;
use App\Http\Middleware\ShopAccess;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(web: __DIR__.'/../routes/web.php', health: '/up')
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(prepend: [BrowserHeaders::class]);
        $middleware->alias(['shop.access' => ShopAccess::class]);
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontFlash(['password', 'password_confirmation', 'code', 'ticket', 'token']);
        // Query bindings and signed URLs can contain private data. Log only the error class.
        $exceptions->report(function (Throwable $error) {
            Log::error('Application request failed', ['type' => $error::class]);
        })->stop();
    })->create();
