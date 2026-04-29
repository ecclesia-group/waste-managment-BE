<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientRegistrationPayment
{
    public function handle(Request $request, Closure $next): Response
    {
        // Registration payment is no longer a blocker for the client app flow.
        return $next($request);
    }
}
