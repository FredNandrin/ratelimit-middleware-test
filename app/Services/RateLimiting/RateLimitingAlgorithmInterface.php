<?php

namespace App\Services\RateLimiting;

interface RateLimitingAlgorithmInterface {
    public function recordRequest(string $token): int;
    public function isRateLimited(string $token): bool;
}