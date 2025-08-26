<?php

declare(strict_types=1);

// Mock das classes globais do Adianti Framework para o teste
namespace {
    class MailService
    {
        public static array $sent = [];
        public static function send($to, $subject, $body, $format): void
        {
            self::$sent[] = compact('to', 'subject', 'body', 'format');
        }
    }
}

namespace Adianti\Registry {
    class TSession
    {
        public static function getValue(string $key): mixed
        {
            return null; // Simula um usuário não logado por padrão
        }
    }
}

// Namespace real do teste
namespace Tests\Handler {

use GOlib\Log\Handler\AdiantiMailerHandler;
use GOlib\Log\Service\MetaLogService;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use MailService;

/**
 * Testa a classe AdiantiMailerHandler.
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 15:40:00 (criação)
 */
final class AdiantiMailerHandlerTest extends TestCase
{
    /**
     * Limpa os dados do MailService antes de cada teste.
     */
    protected function setUp(): void
    {
        MailService::$sent = [];
    }

    /**
     * Testa se o handler envia o e-mail e registra o meta-log corretamente.
     */
    public function testWriteSendsEmailAndLogsMetaMessage(): void
    {
        // 1. Cria um mock para o MetaLogService
        $metaLogServiceMock = $this->createMock(MetaLogService::class);
        
        // 2. Define a expectativa: o método 'log' deve ser chamado uma vez.
        $metaLogServiceMock->expects($this->once())
                           ->method('log')
                           ->with(
                               $this->equalTo('email'), // Verifica se o canal é 'email'
                               $this->stringContains('enviada com sucesso') // Verifica a mensagem
                           );

        // 3. Configurações do handler
        $handlerConfig = [
            'to_address' => 'dest@example.com',
            'subject' => 'Test Subject'
        ];

        // 4. Instancia o handler, injetando o mock
        $handler = new AdiantiMailerHandler($handlerConfig, $metaLogServiceMock);

        // 5. Cria um registro de log para o teste
        $record = new LogRecord(
            new DateTimeImmutable(),
            'test-channel',
            Level::Error,
            'Sample error message',
            ['file' => '/var/www/html/index.php', 'line' => 10]
        );

        // 6. Executa o handler
        $handler->handle($record);

        // 7. Verifica se o e-mail foi "enviado"
        $this->assertCount(1, MailService::$sent);
        $sentEmail = MailService::$sent[0];
        $this->assertSame('dest@example.com', $sentEmail['to']);
        $this->assertSame('Test Subject', $sentEmail['subject']);
        $this->assertSame('html', $sentEmail['format']);
        $this->assertStringContainsString('Sample error message', $sentEmail['body']);
    }
}
}
