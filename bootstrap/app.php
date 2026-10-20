<?php

use App\Exceptions\InvalidCredentialsException;
use App\Exceptions\OrderHasPaymentsException;
use App\Exceptions\OrderNotConfirmedException;
use App\Exceptions\UnsupportedPaymentGatewayException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        $exceptions->render(function (ValidationException $e, Request $request) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $e->errors(),
            ], 422);
        });

        $exceptions->render(function (OrderHasPaymentsException|OrderNotConfirmedException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 409);
        });

        $exceptions->render(function (UnsupportedPaymentGatewayException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (InvalidCredentialsException $e, Request $request) {
            return response()->json(['message' => $e->getMessage()], 401);
        });

        $exceptions->render(function (TokenExpiredException|TokenInvalidException|JWTException $e, Request $request) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
    })->create();
