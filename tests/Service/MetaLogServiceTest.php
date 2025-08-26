<?php

declare(strict_types=1);

namespace Tests\Service;

use PHPUnit\Framework\TestCase;
use GOlib\Log\Service\ConfigService;
use GOlib\Log\Service\MetaLogService;
use GOlib\Log\Contracts\MetadataAgentInterface;

/**
 * Testa a classe MetaLogService.
 *
 * @version    2.0.2
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:35:00 (criação)
 * @date       2025-08-26 18:55:00 (alteração)
 */
final class MetaLogServiceTest extends TestCase
{
    private string $logFilePath;
    private string $testIniPath;
    private ConfigService $configService;

    protected function setUp(): void
    {
        $this->logFilePath = sys_get_temp_dir() . '/meta-test.log';
        $this->testIniPath = sys_get_temp_dir() . '/meta-test-config.ini';

        // CORREÇÃO: Adicionada a seção [monolog_handlers] que o serviço espera.
        $content = <<<INI
[monolog_handlers]
file_handler_enabled = 1

[file_handler]
path = "{$this->logFilePath}"
days = 1
INI;
        file_put_contents($this->testIniPath, $content);

        $this->configService = new ConfigService($this->testIniPath);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testIniPath)) {
            unlink($this->testIniPath);
        }
        if (file_exists($this->logFilePath)) {
            unlink($this->logFilePath);
        }
    }

    public function testLogCreatesFileWithCorrectContent(): void
    {
        $metaLogService = new MetaLogService($this->configService);
        $metaLogService->log('test-channel', 'test message');

        $this->assertFileExists($this->logFilePath);
        $contents = file_get_contents($this->logFilePath);
        $this->assertStringContainsString('[test-channel] test message', $contents);
    }

    public function testLogIncludesMetadataFromAgents(): void
    {
        $metaLogService = new MetaLogService($this->configService);

        $agent = new class implements MetadataAgentInterface {
            public function getMetadata(): array
            {
                return ['user_id' => 123, 'request_id' => 'xyz-789'];
            }
        };
        $metaLogService->addAgent($agent);

        $metaLogService->log('agent-test', 'message with metadata');

        $contents = file_get_contents($this->logFilePath);
        // CORREÇÃO: Garante que o conteúdo é uma string antes de verificar.
        $this->assertIsString($contents);
        $this->assertStringContainsString('"user_id":123', $contents);
        $this->assertStringContainsString('"request_id":"xyz-789"', $contents);
    }
}
