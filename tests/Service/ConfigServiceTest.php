<?php

declare(strict_types=1);

namespace Tests\Service;

use RuntimeException;
use PHPUnit\Framework\TestCase;
use GOlib\Log\Service\ConfigService;

/**
 * Testa a classe ConfigService.
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:30:00 (criação)
 */
final class ConfigServiceTest extends TestCase
{
    private string $testIniPath;

    /**
     * Configura o ambiente de teste criando um arquivo .ini temporário.
     */
    protected function setUp(): void
    {
        $this->testIniPath = sys_get_temp_dir() . '/test-config.ini';
        $content = <<<INI
[logging]
environment = "development"

[file_handler]
enabled = 1
path = "files/logs/test.log"
INI;
        file_put_contents($this->testIniPath, $content);
    }

    /**
     * Limpa o ambiente de teste removendo o arquivo .ini temporário.
     */
    protected function tearDown(): void
    {
        if (file_exists($this->testIniPath)) {
            unlink($this->testIniPath);
        }
    }

    /**
     * Testa se a classe é instanciada corretamente e lê os valores do .ini.
     */
    public function testGetReturnsCorrectValue(): void
    {
        $configService = new ConfigService($this->testIniPath);

        // Testa a leitura de uma chave específica
        $this->assertSame('development', $configService->get('logging', 'environment'));
        // Testa a leitura de uma seção inteira
        $this->assertEquals(['enabled' => '1', 'path' => 'files/logs/test.log'], $configService->get('file_handler'));
        // Testa o retorno do valor padrão para uma chave inexistente
        $this->assertNull($configService->get('logging', 'non_existent_key'));
        $this->assertSame('default_value', $configService->get('logging', 'non_existent_key', 'default_value'));
    }

    /**
     * Testa se uma exceção é lançada quando o arquivo .ini não é encontrado.
     */
    public function testThrowsExceptionWhenFileMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Arquivo de configuração de log não encontrado');
        
        new ConfigService('non_existent_file.ini');
    }

    /**
     * Testa o novo método de validação para chaves ausentes.
     */
    public function testValidateThrowsExceptionOnMissingKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("A chave de configuração obrigatória 'level' na seção 'file_handler' está ausente.");

        $configService = new ConfigService($this->testIniPath);
        
        // Valida a existência de uma chave que sabemos que não está no .ini de teste
        $configService->validate(['file_handler' => ['path', 'level']]);
    }
}
