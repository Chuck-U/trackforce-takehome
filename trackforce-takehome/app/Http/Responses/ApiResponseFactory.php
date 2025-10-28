<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class ApiResponseFactory
{
    /**
     * Create a successful response
     *
     * @param array $data
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function success(array $data, int $statusCode = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Create an error response
     *
     * @param array $error
     * @param int $statusCode
     * @return JsonResponse
     */
    public static function error(array $error, int $statusCode = 400): JsonResponse
    {
        return response()->json([
            'success' => false,
            'error' => $error,
        ], $statusCode);
    }

    /**
     * Create a validation error response
     *
     * @param array $details
     * @return JsonResponse
     */
    public static function validationError(array $details): JsonResponse
    {
        return self::error([
            'code' => 'VALIDATION_ERROR',
            'message' => 'Invalid employee data',
            'details' => $details,
        ], 400);
    }

    /**
     * Create a TrackTik API error response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function trackTikError(string $message): JsonResponse
    {
        return self::error([
            'code' => 'TRACKTIK_ERROR',
            'message' => $message,
        ], 500);
    }

    /**
     * Create an internal server error response
     *
     * @param string $message
     * @return JsonResponse
     */
    public static function internalError(string $message = 'An error occurred while processing the employee data'): JsonResponse
    {
        return self::error([
            'code' => 'INTERNAL_ERROR',
            'message' => $message,
        ], 500);
    }
}
