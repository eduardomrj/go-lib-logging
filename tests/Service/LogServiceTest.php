<?php

declare(strict_types=1);

namespace Tests\Service;

use App\Lib\GOlib\Log\Service\LogService;
use App\Lib\GOlib\Log\Service\ConfigService;
use Monolog\Handler\RotatingFileHandler;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class LogServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $ref = new ReflectionClass(ConfigService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);

        $tempDir = sys_get_temp_dir() . '/goliblogs';
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $prop->setValue(null, [
            'general' => ['application' => 'TESTAPP'],
            'monolog_handlers' => ['file_handler_enabled' => true],
            'file_handler' => [
                'path' => $tempDir . '/app.log',
                'days' => 1,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $ref = new ReflectionClass(LogService::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $refConfig = new ReflectionClass(ConfigService::class);
        $propConfig = $refConfig->getProperty('config');
        $propConfig->setAccessible(true);
        $propConfig->setValue(null, null);
    }

    public function testSingletonAndFileHandler(): void
    {
        $service1 = LogService::getInstance();
        $service2 = LogService::getInstance();
        $this->assertSame($service1, $service2);

        $handlers = $service1->getLogger()->getHandlers();
        $this->assertNotEmpty($handlers);
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
    }
}
