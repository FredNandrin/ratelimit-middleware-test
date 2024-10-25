<?php

namespace App\Http\Middleware;

use App\Services\RateLimiting\Exception\RateLimitExceededException;
use App\Services\RateLimiting\RateLimitingAlgorithm;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class RateLimitingMiddleware
{
        public function __construct(private RateLimitingAlgorithm $algorithm)
    {

    }

    /**
     * @throws RateLimitExceededException
     */
    final public function handle(Request $request, Closure $next): Response
    {
        $token = $request->get('token', $request->getClientIp());

        $requestsLeft = $this->algorithm->recordRequest($token);
        if ($requestsLeft <= 0) {
            // only thrown in very specific racing conditions
            throw new RateLimitExceededException();
        }
        return $next($request);
    }
}
