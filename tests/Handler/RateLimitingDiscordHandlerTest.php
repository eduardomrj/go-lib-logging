<?php

declare(strict_types=1);

namespace Tests\Handler;

use Monolog\Level;
use DateTimeImmutable;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use GOlib\Log\Service\MetaLogService;
use GOlib\Log\Contracts\CacheInterface;
use GOlib\Log\Handler\RateLimitingDiscordHandler;

/**
 * Testa a classe RateLimitingDiscordHandler.
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:45:00 (criação)
 */
final class RateLimitingDiscordHandlerTest extends TestCase
{
    private $cacheMock;
    private $metaLogServiceMock;
    private LogRecord $record;

    /**
     * Configura os mocks e um registro de log padrão para os testes.
     */
    protected function setUp(): void
    {
        $this->cacheMock = $this->createMock(CacheInterface::class);
        $this->metaLogServiceMock = $this->createMock(MetaLogService::class);
        $this->record = new LogRecord(new DateTimeImmutable(), 'test', Level::Error, 'test message');
    }

    /**
     * Testa se o handler bloqueia uma segunda chamada e registra o meta-log.
     */
    public function testRateLimitingBlocksSecondCallAndLogsMetaMessage(): void
    {
        // 1. Configura o mock do cache para simular o rate limit
        $this->cacheMock->method('get')
                        ->with($this->equalTo('discord_log_timestamps'))
                        // Na primeira chamada, retorna um array vazio (sem timestamps)
                        // Na segunda, retorna um array com um timestamp, atingindo o limite de 1
                        ->willReturnOnConsecutiveCalls([], [time()]);

        // 2. Espera que o método 'set' do cache seja chamado
        $this->cacheMock->expects($this->any()) // Pode ser chamado uma ou mais vezes
                        ->method('set');

        // 3. Define a expectativa para o meta-log: deve ser chamado uma vez com a mensagem de bloqueio
        $this->metaLogServiceMock->expects($this->once())
                                 ->method('log')
                                 ->with(
                                     $this->equalTo('discord'),
                                     $this->stringContains('bloqueada por Rate Limit')
                                 );

        // 4. Instancia o handler com um limite de 1 mensagem por minuto
        $handler = new RateLimitingDiscordHandler(
            'https://example.com',
            1, // maxPerMinute
            $this->cacheMock,
            $this->metaLogServiceMock
        );

        // 5. Executa o handler duas vezes
        $handler->handle($this->record); // Primeira chamada (deve passar)
        $handler->handle($this->record); // Segunda chamada (deve ser bloqueada e logar)
    }

    /**
     * Testa se o handler NÃO bloqueia a primeira chamada.
     * Este teste foca em garantir que o log de "rate limit" NÃO seja chamado.
     */
    public function testWriteDoesNotBlockFirstCall(): void
    {
        // 1. Configura o cache para sempre retornar um array vazio (sem timestamps)
        $this->cacheMock->method('get')->willReturn([]);
        $this->cacheMock->expects($this->once())->method('set');

        // 2. Define a expectativa: o método 'log' NUNCA deve ser chamado com a mensagem de bloqueio
        $this->metaLogServiceMock->expects($this->never())
                                 ->method('log')
                                 ->with(
                                     $this->equalTo('discord'),
                                     $this->stringContains('bloqueada por Rate Limit')
                                 );

        // 3. Instancia o handler
        $handler = new RateLimitingDiscordHandler(
            'https://example.com',
            5, // maxPerMinute
            $this->cacheMock,
            $this->metaLogServiceMock
        );

        // 4. Executa o handler uma vez
        // O teste passará se a expectativa do 'never()' for cumprida.
        // Nota: Não estamos testando o envio real via cURL, apenas a lógica de rate limit.
        $handler->handle($this->record);
    }
}
