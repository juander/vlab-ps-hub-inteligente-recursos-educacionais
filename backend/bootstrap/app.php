<?php

use App\Exceptions\GeminiException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {})
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (GeminiException $exception, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $exception->getStatusCode();

                return response()->json([
                    'error' => $exception->getMessage(),
                ], $status);
            }

            return null;
        });
    })
    ->create();
