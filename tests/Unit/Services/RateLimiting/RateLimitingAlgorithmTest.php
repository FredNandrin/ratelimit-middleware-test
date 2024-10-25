<?php

namespace Tests\Unit\Services\RateLimiting;

use App\Services\RateLimiting\Exception\RateLimitExceededException;
use App\Services\RateLimiting\RateLimitingAlgorithm;
use Illuminate\Redis\RedisManager;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class RateLimitingAlgorithmTest extends TestCase
{
    private array $redisData;
    private RedisManager $redisMock;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->redisData=[];
        $this->redisMock = $this->createMock(RedisManager::class);
        $this->redisMock->expects($this->any())
            ->method('__call')
            ->with($this->logicalOr(
                $this->equalTo('command', ['KEYS',['user_test_*']]),
                $this->equalTo('command', ['SET'])
            ))
            ->willReturnCallback(array($this, 'redisMockCallback'));
    }

    #[Test]
    final public function test1(): void
    {
        $test = new RateLimitingAlgorithm( $this->redisMock,60);
        $this->assertEquals(59, $test->recordRequest('test'));
        $this->assertEquals(58, $test->recordRequest('test'));
        $this->assertEquals(57, $test->recordRequest('test'));
        $this->assertEquals(56, $test->recordRequest('test'));
        $this->assertEquals(55, $test->recordRequest('test'));
    }

    #[Test]
    final public function testLowLimit(): void
    {
        $test = new RateLimitingAlgorithm( $this->redisMock,0);
        $this->expectException(RateLimitExceededException::class);
        $this->assertEquals(0, $test->recordRequest('test'));

    }

    #[Test]
    final public function testExceedingLimit(): void
    {
        $test = new RateLimitingAlgorithm( $this->redisMock,2);

        $this->assertFalse($test->isRateLimited('test'), 'isRateLimited should return false');
        $this->assertEquals(1, $test->recordRequest('test'), 'recordRequest should return 1');
        try {
            $this->assertFalse($test->isRateLimited('test'));
            $this->assertEquals(0, $test->recordRequest('test'), 'recordRequest should return 0');
            $this->assertEquals(0, $test->recordRequest('test'),    'recordRequest should throw RateLimitExceededException');
            $this->fail('Last recordRequest should have thrown RateLimitExceededException');
        } catch (RateLimitExceededException $e) {
            $this->assertTrue($test->isRateLimited('test'));
            $this->expectException(RateLimitExceededException::class);
            $this->assertEquals(0, $test->recordRequest('test'), 'recordRequest should return 0c');
        }
        $this->assertTrue($test->isRateLimited('test'));
    }

    final public function redisMockCallback(string $command, array $param): null|string|array {
        switch ($param[0]) {
            case 'KEYS':
                return $this->redisData;
            case 'set':
                $this->redisData[] = $param[1][0];
                return 'OK';
        }
        return null;
    }
}
