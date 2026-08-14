<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Resolve the "rows per page" for a paginated index, constrained to a fixed
     * set of options so users can't request an arbitrarily large page size.
     */
    protected function perPage(Request $request, int $default = 20): int
    {
        $value = (int) $request->input('per_page', $default);

        return in_array($value, [10, 25, 50, 100], true) ? $value : $default;
    }

    /**
     * Resolve a "search" input for a LIKE query, capped to a sane length. Not an injection
     * concern (Query Builder parameter-binds it) — just avoids scanning an unbounded string.
     */
    protected function searchTerm(Request $request, string $key = 'search', int $maxLength = 100): ?string
    {
        $value = trim((string) $request->input($key, ''));

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
