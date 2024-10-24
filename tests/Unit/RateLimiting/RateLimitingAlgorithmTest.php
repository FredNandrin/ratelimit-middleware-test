<?php

namespace Tests\Services\RateLimiting;

use App\Services\RateLimiting\RateLimitingAlgorithm;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Framework\TestCase;

class RateLimitingAlgorithmTest extends TestCase
{

    #[Test]
    #[CoversClass(RateLimitingAlgorithm::class)]
    final public function test1(): void
    {
        Redis::spy();

        $limit = 60;

        // expect class instanciation to throw exception
        $test = new RateLimitingAlgorithm( $limit);
        $this->assertEquals($limit, $test->recordRequest('test'));
        $this->assertEquals($limit-1, $test->recordRequest('test'));
        $test = new RateLimitingAlgorithm( 0);
        $this->expectException(\App\Services\RateLimiting\Exception\RateLimitExceededException::class);
        $this->assertEquals($limit, $test->recordRequest('test'));
    }

}
