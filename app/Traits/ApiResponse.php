<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function successResponse(
        string $message,
        mixed $data = null,
        int $statusCode = 200
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    protected function errorResponse(
        string $message,
        int $statusCode = 400,
        mixed $errors = null
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $statusCode);
    }
    protected function notFoundResponse(
        string $message = 'Resource not found',
        mixed $errors = null
    ): JsonResponse {
        return $this->errorResponse($message, 404, $errors);
    }
    protected function createdResponse(
        string $message,
        mixed $data = null
    ): JsonResponse {
        return $this->successResponse($message, $data, 201);
    }
}
