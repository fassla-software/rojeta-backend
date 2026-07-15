<?php

namespace App\Support;

use Illuminate\Http\Request;

class PaginationHelper
{
    public static function fromRequest(Request $request, int $defaultLimit = 20, int $maxLimit = 100): array
    {
        $page = max(1, (int) $request->query('page', 1));
        $limit = min($maxLimit, max(1, (int) $request->query('limit', $defaultLimit)));

        return [$page, $limit];
    }
}
