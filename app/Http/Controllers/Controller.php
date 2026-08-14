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
     * Resolve a "search" input for a LIKE query, capped to a sane length. Not a SQL injection
     * concern — Query Builder always parameter-binds this value regardless of length — but an
     * unbounded string repeated into a "%...%" scan on every keystroke of a debounced search box
     * is needless work for no benefit, so it's capped here rather than left open-ended.
     */
    protected function searchTerm(Request $request, string $key = 'search', int $maxLength = 100): ?string
    {
        $value = trim((string) $request->input($key, ''));

        return $value === '' ? null : mb_substr($value, 0, $maxLength);
    }
}
