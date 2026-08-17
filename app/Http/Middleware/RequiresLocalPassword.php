<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresLocalPassword
{
    /**
     * Hide password-management screens from Members with no local password.
     *
     * Runs before password.confirm, which would otherwise trap a
     * passwordless Member at a confirmation form they can never pass.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user()?->password === null, 404);

        return $next($request);
    }
}
