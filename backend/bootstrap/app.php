<?php

use App\Catalogue\CatalogueUnavailable;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // The underlying cause is still logged. Clients only get a generic message.
        $exceptions->render(fn (CatalogueUnavailable $e) => response()->json(
            ['message' => 'The product catalogue is temporarily unavailable. Please try again shortly.'],
            503,
        ));
    })->create();
