<?php

declare(strict_types=1);

namespace Tests\Handler;

use App\Lib\GOlib\Log\Handler\RateLimitingDiscordHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class RateLimitingDiscordHandlerTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/discord_cache.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->cacheFile)) {
            unlink($this->cacheFile);
        }
    }

    public function testRateLimitingMechanism(): void
    {
        $handler = new RateLimitingDiscordHandler('https://example.com', 1, Level::Debug);

        $ref = new ReflectionClass(RateLimitingDiscordHandler::class);
        $prop = $ref->getProperty('cacheFile');
        $prop->setAccessible(true);
        $prop->setValue($handler, $this->cacheFile);

        $method = $ref->getMethod('isRateLimited');
        $method->setAccessible(true);

        $this->assertFalse($method->invoke($handler));
        $this->assertTrue($method->invoke($handler));
    }
}
