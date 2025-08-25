<?php

declare(strict_types=1);

namespace Tests\Service;

use GOlib\Log\Service\ConfigService;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;

final class ConfigServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        $ref = new ReflectionClass(ConfigService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    public function testThrowsWhenConfigFileMissing(): void
    {
        $this->expectException(RuntimeException::class);
        ConfigService::get('logging');
    }
}
