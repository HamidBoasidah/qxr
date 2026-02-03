<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions
     */
    protected function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        // Handle custom exceptions (new base name + legacy alias)
        if ($e instanceof \App\Exceptions\ApplicationException || $e instanceof \App\Exceptions\ApplicationException) {
            return $e->render($request);
        }

        // Handle validation exceptions
        if ($e instanceof ValidationException) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'error_code' => 'VALIDATION_ERROR',
                'status_code' => 422,
                'errors' => $e->errors(),
                'timestamp' => now()->toISOString(),
            ], 422);
        }

        // Handle authentication exceptions
        if ($e instanceof AuthenticationException) {
            return response()->json([
                'success' => false,
                'message' => 'يجب تسجيل الدخول للوصول لهذا المورد',
                'error_code' => 'UNAUTHENTICATED',
                'status_code' => 401,
                'timestamp' => now()->toISOString(),
            ], 401);
        }

        // Handle model not found exceptions
        if ($e instanceof ModelNotFoundException) {
            return response()->json([
                'success' => false,
                'message' => 'المورد المطلوب غير موجود',
                'error_code' => 'MODEL_NOT_FOUND',
                'status_code' => 404,
                'timestamp' => now()->toISOString(),
            ], 404);
        }

        // Handle query exceptions
        if ($e instanceof QueryException) {
            // Handle duplicate entry / unique constraint violations generically
            if ($e->getCode() == 23000 && str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'success' => false,
                    'message' => 'البيانات المراد إدخالها موجودة بالفعل',
                    'error_code' => 'DUPLICATE_ENTRY',
                    'status_code' => 422,
                    'timestamp' => now()->toISOString(),
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'خطأ في قاعدة البيانات',
                'error_code' => 'DATABASE_ERROR',
                'status_code' => 500,
                'timestamp' => now()->toISOString(),
            ], 500);
        }

        // Handle HTTP not found exceptions
        if ($e instanceof NotFoundHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'الصفحة المطلوبة غير موجودة',
                'error_code' => 'ROUTE_NOT_FOUND',
                'status_code' => 404,
                'timestamp' => now()->toISOString(),
            ], 404);
        }

        // Handle method not allowed exceptions
        if ($e instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'طريقة الطلب غير مسموح بها',
                'error_code' => 'METHOD_NOT_ALLOWED',
                'status_code' => 405,
                'timestamp' => now()->toISOString(),
            ], 405);
        }

        // Handle access denied exceptions
        if ($e instanceof AccessDeniedHttpException) {
            return response()->json([
                'success' => false,
                'message' => 'ممنوع الوصول لهذا المورد',
                'error_code' => 'ACCESS_DENIED',
                'status_code' => 403,
                'timestamp' => now()->toISOString(),
            ], 403);
        }

        // Handle general exceptions
        return response()->json([
            'success' => false,
            'message' => config('app.debug') ? $e->getMessage() : 'حدث خطأ غير متوقع',
            'error_code' => 'INTERNAL_SERVER_ERROR',
            'status_code' => 500,
            'timestamp' => now()->toISOString(),
        ], 500);
    }
} 