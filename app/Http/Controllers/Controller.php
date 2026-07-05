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
}
