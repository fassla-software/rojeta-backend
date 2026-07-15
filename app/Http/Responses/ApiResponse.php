<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

class ApiResponse
{
    public static function data(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }

    public static function paginated(LengthAwarePaginator $paginator, mixed $data = null): JsonResponse
    {
        return response()->json([
            'data' => $data ?? $paginator->items(),
            'meta' => [
                'page' => $paginator->currentPage(),
                'limit' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public static function message(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        $payload = ['message' => $message];

        if ($data !== null) {
            $payload['data'] = $data;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $error, string $message, int $status = 400, ?array $details = null): JsonResponse
    {
        $payload = [
            'error' => $error,
            'message' => $message,
        ];

        if ($details !== null) {
            $payload['details'] = $details;
        }

        return response()->json($payload, $status);
    }
}
