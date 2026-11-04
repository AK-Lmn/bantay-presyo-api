<?php

namespace App\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class ApiExceptionRenderer
{
    /**
     * @var array<int, array{0: string, 1: string}>
     */
    private const HTTP_ERRORS = [
        400 => ['bad_request', 'The request could not be processed.'],
        401 => ['unauthenticated', 'Authentication is required.'],
        403 => ['forbidden', 'You are not allowed to perform this action.'],
        404 => ['not_found', 'The requested resource was not found.'],
        405 => ['method_not_allowed', 'This method is not supported for this route.'],
        409 => ['conflict', 'The request conflicts with the current state of the resource.'],
        429 => ['too_many_requests', 'Too many requests. Try again later.'],
    ];

    public static function render(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::error(422, 'validation_failed', 'The given data was invalid.', $e->errors());
        }

        if ($e instanceof AuthenticationException) {
            return self::error(401, ...self::HTTP_ERRORS[401]);
        }

        if ($e instanceof HttpExceptionInterface) {
            $status = $e->getStatusCode();
            [$code, $message] = self::HTTP_ERRORS[$status] ?? ['http_error', $e->getMessage() ?: 'Request failed.'];

            return self::error($status, $code, $message, headers: $e->getHeaders());
        }

        $details = config('app.debug')
            ? ['exception' => $e::class, 'message' => $e->getMessage()]
            : null;

        return self::error(500, 'server_error', 'Something went wrong on our end.', $details);
    }

    /**
     * @param  array<string, mixed>|null  $details
     * @param  array<string, string>  $headers
     */
    private static function error(int $status, string $code, string $message, ?array $details = null, array $headers = []): JsonResponse
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status, $headers);
    }
}
