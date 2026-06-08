<?php

namespace App\Exceptions;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Handle unauthenticated users for API routes
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // Check if this is an API request
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
                'errors' => null,
            ], 401);
        }

        // For web routes, redirect to login
        return redirect()->guest(route('login'));
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // Handle API routes with JSON responses
        if ($request->expectsJson() || $request->is('api/*')) {

            // Handle 404 errors
            if ($exception instanceof NotFoundHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Route not found',
                    'errors' => null,
                ], 404);
            }

            // Handle validation errors
            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $exception->errors(),
                ], 422);
            }

            // Handle authentication errors
            if ($exception instanceof AuthenticationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated',
                    'errors' => null,
                ], 401);
            }

            // Handle authorization errors
            if ($exception instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                    'errors' => null,
                ], 403);
            }

            // Handle method not allowed
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed',
                    'errors' => null,
                ], 405);
            }

            // Handle general server errors
            if ($exception instanceof \Exception) {
                $message = config('app.debug') ? $exception->getMessage() : 'Server Error';

                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'errors' => config('app.debug') ? [
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ] : null,
                ], 500);
            }
        }

        return parent::render($request, $exception);
    }
}
