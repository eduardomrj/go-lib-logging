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
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:50:00 (criação)
 */
final class LogServiceTest extends TestCase
{
    private $configServiceMock;
    private $metaLogServiceMock;

    /**
     * Configura os mocks para os serviços de dependência.
     */
    protected function setUp(): void
    {
        $this->configServiceMock = $this->createMock(ConfigService::class);
        $this->metaLogServiceMock = $this->createMock(MetaLogService::class);
    }

    /**
     * Testa se o LogService instancia e adiciona os handlers corretos com base na configuração.
     */
    public function testLogServiceAddsHandlersBasedOnConfig(): void
    {
        // 1. Simula o que o ConfigService retornaria ao ser consultado
        $this->configServiceMock->method('get')
            ->willReturnMap([
                // Configuração para o nome da aplicação
                ['general', 'application', 'TEST-APP', 'TEST-APP'],
                // Configurações para os handlers
                ['file_handler', 'enabled', false, true],
                ['file_handler', null, [], ['path' => 'test.log', 'level' => 'DEBUG']],
                ['discord_handler', 'enabled', false, true],
                ['discord_handler', null, [], ['webhook_url' => 'https://fake-url.com', 'level' => 'ERROR']],
                ['email_handler', 'enabled', false, false], // Email desabilitado
                // Configuração para a estratégia de notificação
                ['notification_strategy', null, [], ['use_fingers_crossed' => false]]
            ]);

        // 2. Instancia o LogService com os mocks
        $logService = new LogService($this->configServiceMock, $this->metaLogServiceMock);
        $logger = $logService->getLogger();
        $handlers = $logger->getHandlers();

        // 3. Verifica os resultados
        // Esperamos 2 handlers: File e Discord
        $this->assertCount(2, $handlers);
        // Verifica se o primeiro handler é o de arquivo
        $this->assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
        // Verifica se o segundo handler é o do Discord
        $this->assertInstanceOf(RateLimitingDiscordHandler::class, $handlers[1]);
        // Verifica se o nome do logger foi definido corretamente
        $this->assertSame('TEST-APP', $logger->getName());
    }
}
