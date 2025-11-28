<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(App\Http\Middleware\ForceJsonResponse::class);
        $middleware->append(App\Http\Middleware\CrossOrigin::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            Log::info($e);
            return response()->json([
                "data" => [
                    "status_code" => 404,
                    "message" => "Action Unsuccessful",
                    "in_error" => true,
                    "reason" => "Resource cannot be found",
                    "data" => [],
                    "point_in_time" => now()
                ]
            ], 404);
        });

    })->create();
