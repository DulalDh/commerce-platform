<?php

namespace App\Support;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Spatie\Permission\Exceptions\UnauthorizedException as PermissionUnauthorizedException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiResponse
{
    public static function success(mixed $data = null, array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json(array_filter([
            'data' => $data,
            'meta' => $meta ?: null,
        ], fn ($v) => $v !== null), $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'errors' => $errors ?: null,
        ], fn ($v) => $v !== null), $status);
    }

    public static function fromException(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ValidationException => self::error(
                'The given data was invalid.',
                $e->errors(),
                422
            ),
            $e instanceof AuthenticationException => self::error(
                'Unauthenticated.',
                [],
                401
            ),
            $e instanceof AuthorizationException, $e instanceof PermissionUnauthorizedException => self::error(
                'This action is unauthorized.',
                [],
                403
            ),
            $e instanceof ModelNotFoundException, $e instanceof NotFoundHttpException => self::error(
                'Resource not found.',
                [],
                404
            ),
            $e instanceof InvalidArgumentException => self::error(
                $e->getMessage(),
                [],
                422
            ),
            default => self::error(
                app()->hasDebugModeEnabled() ? $e->getMessage() : 'Server error.',
                [],
                500
            ),
        };
    }
}
