<?php

namespace App\Services\RateLimiting;

use App\Services\RateLimiting\Exception\RateLimitExceededException;
use Illuminate\Redis\RedisManager;

readonly class RateLimitingAlgorithm implements RateLimitingAlgorithmInterface
{
    public function __construct(private RedisManager $redis,private int $limit = 60)
    {
    }

    /**
     * @throws RateLimitExceededException
     * @noinspection MethodShouldBeFinalInspection
     */
    public function recordRequest(string $token): int
    {
        // Check if requests are available
        if ($this->isRateLimited($token)) {
            throw new RateLimitExceededException();
        }
        // add new key to redis
        $value = date_timestamp_get(date_create());
        $key = $this->getKeyFromToken($token).uniqid(''.$value, true);
        $this->redis->command('set', [ $key, $value, 'EX', 60]);

        return $this->availableRequestsLeft($token);
    }

    final public function isRateLimited(string $token): bool
    {
        return $this->availableRequestsLeft($token) <= 0;
    }

    private function getKeyFromToken(string $token): string
    {
        return 'user_'.$token.'_';
    }

    private function availableRequestsLeft(string $token): int
    {
        $unexpiredKeys = $this->redis->command('KEYS', [$this->getKeyFromToken($token).'*']);
        $unexpiredKeysCount = is_null($unexpiredKeys)?0:count($unexpiredKeys);
        return  $this->limit - $unexpiredKeysCount;
    }
}
