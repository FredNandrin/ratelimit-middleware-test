<?php
declare(strict_types=1);

namespace App\Services\RateLimiting\Exception;

use App\Services\RateLimiting\Exception;

class RateLimitExceededException extends \Exception
{
    public function __construct()
    {
        parent::__construct('Rate limit exceeded', 429);
    }
}