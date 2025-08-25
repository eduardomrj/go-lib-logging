<?php

declare(strict_types=1);

namespace Tests\Service;

use GOlib\Log\Service\MetaLogService;
use GOlib\Log\Service\ConfigService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MetaLogServiceTest extends TestCase
{
    private string $logFileBase;

    protected function setUp(): void
    {
        $this->logFileBase = sys_get_temp_dir() . '/meta-log';
        $ref = new ReflectionClass(ConfigService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, [
            'monolog_handlers' => ['file_handler_enabled' => true],
            'file_handler' => ['path' => $this->logFileBase . '.log', 'days' => 1],
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->logFileBase . '*') as $file) {
            unlink($file);
        }

        $ref = new ReflectionClass(MetaLogService::class);
        $prop = $ref->getProperty('instance');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        $refConfig = new ReflectionClass(ConfigService::class);
        $propConfig = $refConfig->getProperty('config');
        $propConfig->setAccessible(true);
        $propConfig->setValue(null, null);
    }

    public function testLogCreatesFile(): void
    {
        MetaLogService::log('test', 'example message');

        $files = glob($this->logFileBase . '*');
        $this->assertNotEmpty($files);
        $contents = file_get_contents($files[0]);
        $this->assertStringContainsString('[test] example message', $contents);
    }
}
