<?php

declare(strict_types=1);

namespace Dashworthy\PestPluginVisualizations\Tests\Fixtures\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Refuses every request with the given status.
 *
 * The endpoint assertions describe what a refusing route stack looks like from
 * outside, so the tests need a stack that refuses without pulling a real guard
 * or gate into the package's fixtures.
 */
final class RefusesWithStatus
{
    public function handle(Request $request, Closure $next, string $status = '403'): mixed
    {
        abort((int) $status);
    }
}
