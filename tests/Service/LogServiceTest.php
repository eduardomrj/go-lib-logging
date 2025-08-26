<?php

declare(strict_types=1);

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use GOlib\Log\Service\LogService;
use GOlib\Log\Service\ConfigService;
use GOlib\Log\Service\MetaLogService;
use Monolog\Handler\RotatingFileHandler;
use GOlib\Log\Handler\RateLimitingDiscordHandler;

/**
 * Testa a classe LogService.
 *
 * @version    2.0.2
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:50:00 (criação)
 * @date       2025-08-26 18:55:00 (alteração)
 */
final class LogServiceTest extends TestCase
{
    private $configServiceMock;
    private $metaLogServiceMock;

    protected function setUp(): void
    {
        $this->configServiceMock = $this->createMock(ConfigService::class);
        $this->metaLogServiceMock = $this->createMock(MetaLogService::class);
    }

    public function testLogServiceAddsHandlersBasedOnConfig(): void
    {
        // CORREÇÃO: O mapa agora reflete a nova estrutura do .ini
        $this->configServiceMock->method('get')
            ->willReturnMap([
                ['general', 'application', 'GOLIB-APP', 'TEST-APP'],
                // O LogService agora verifica 'enabled' dentro de cada seção de handler
                ['file_handler', 'enabled', false, true],
                ['file_handler', null, [], ['path' => sys_get_temp_dir() . '/test.log', 'level' => 'DEBUG']],
                ['discord_handler', 'enabled', false, true],
                ['discord_handler', null, [], ['webhook_url' => 'https://example.com', 'level' => 'ERROR']],
                ['email_handler', 'enabled', false, false],
                ['notification_strategy', null, [], ['use_fingers_crossed' => false]]
            ]);

        $logService = new LogService($this->configServiceMock, $this->metaLogServiceMock);
        $logger = $logService->getLogger();
        $handlers = $logger->getHandlers();

        $this->assertCount(2, $handlers);
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
        $this->assertInstanceOf(RateLimitingDiscordHandler::class, $handlers[1]);
        $this->assertSame('TEST-APP', $logger->getName());
    }
}
