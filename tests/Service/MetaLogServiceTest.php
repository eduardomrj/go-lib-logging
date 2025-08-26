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
 * @version    2.0.1
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:35:00 (criação)
 * @date       2025-08-26 18:30:00 (alteração)
 */
final class MetaLogServiceTest extends TestCase
{
    private string $logFilePath;
    private string $testIniPath;
    private ConfigService $configService;

    /**
     * Configura o ambiente de teste.
     */
    protected function setUp(): void
    {
        $this->logFilePath = sys_get_temp_dir() . '/meta-test.log';
        $this->testIniPath = sys_get_temp_dir() . '/meta-test-config.ini';

        // CORREÇÃO: Adicionada a seção [monolog_handlers] que o serviço espera.
        $content = <<<INI
[monolog_handlers]
file_handler_enabled = 1

[file_handler]
enabled = 1
path = "{$this->logFilePath}"
days = 1
INI;
        file_put_contents($this->testIniPath, $content);

        $this->configService = new ConfigService($this->testIniPath);
    }

    /**
     * Limpa o ambiente de teste.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->testIniPath)) {
            unlink($this->testIniPath);
        }
        if (file_exists($this->logFilePath)) {
            unlink($this->logFilePath);
        }
    }

    /**
     * Testa se o log básico funciona e cria o arquivo de log.
     */
    public function testLogCreatesFileWithCorrectContent(): void
    {
        $metaLogService = new MetaLogService($this->configService);
        $metaLogService->log('test-channel', 'test message');

        $this->assertFileExists($this->logFilePath);

        $contents = file_get_contents($this->logFilePath);
        $this->assertStringContainsString('[test-channel] test message', $contents);
    }

    /**
     * Testa se o serviço anexa corretamente os metadados dos agentes.
     */
    public function testLogIncludesMetadataFromAgents(): void
    {
        $metaLogService = new MetaLogService($this->configService);

        // Cria e adiciona um agente de metadados mock
        $agent = new class implements MetadataAgentInterface {
            public function getMetadata(): array
            {
                return ['user_id' => 123, 'request_id' => 'xyz-789'];
            }
        };
        $metaLogService->addAgent($agent);

        $metaLogService->log('agent-test', 'message with metadata');

        $contents = file_get_contents($this->logFilePath);
        // Verifica se o contexto no log contém os metadados do agente em formato JSON
        $this->assertStringContainsString('"user_id":123', $contents);
        $this->assertStringContainsString('"request_id":"xyz-789"', $contents);
    }

    /**
     * Testa se o log não é escrito quando o file_handler está desabilitado.
     */
    public function testLogDoesNotWriteWhenDisabled(): void
    {
        // Cria uma nova configuração com o handler desabilitado
        $disabledIniPath = sys_get_temp_dir() . '/disabled-config.ini';
        file_put_contents($disabledIniPath, "[monolog_handlers]\nfile_handler_enabled = 0");
        $disabledConfigService = new ConfigService($disabledIniPath);

        $metaLogService = new MetaLogService($disabledConfigService);
        $metaLogService->log('disabled-test', 'should not be logged');

        $this->assertFileDoesNotExist($this->logFilePath);

        unlink($disabledIniPath);
    }
}
