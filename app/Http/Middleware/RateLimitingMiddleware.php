<?php

namespace App\Http\Middleware;

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
     * Handle an incoming request.
     *
     * @param \Closure(Request): (Response) $next
     *
     * @throws \App\Services\RateLimiting\Exception\RateLimitExceededException
     */
    final public function handle(Request $request, Closure $next): Response
    {
        $token = $request->get('token', $request->getClientIp());

        $requestsLeft = $this->algorithm->recordRequest($token);

        if ($requestsLeft <= 0) {
            throw new \App\Services\RateLimiting\Exception\RateLimitExceededException();
        }
        return new \Illuminate\Http\Response($requestsLeft);
        return $next($request);
    }
}
