<?php

declare(strict_types=1);

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
            return null;
        }
    }
}

namespace Tests\Handler {

use App\Lib\GOlib\Log\Handler\AdiantiMailerHandler;
use App\Lib\GOlib\Log\Service\ConfigService;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use MailService;

final class AdiantiMailerHandlerTest extends TestCase
{
    protected function setUp(): void
    {
        MailService::$sent = [];
        $ref = new ReflectionClass(ConfigService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, ['monolog_handlers' => ['file_handler_enabled' => false]]);
    }

    protected function tearDown(): void
    {
        $refConfig = new ReflectionClass(ConfigService::class);
        $propConfig = $refConfig->getProperty('config');
        $propConfig->setAccessible(true);
        $propConfig->setValue(null, null);
    }

    public function testWriteSendsEmailViaMailService(): void
    {
        $handler = new AdiantiMailerHandler([
            'to_address' => 'dest@example.com',
            'subject' => 'Test Subject'
        ]);

        $record = new LogRecord(
            new DateTimeImmutable(),
            'test',
            Level::Error,
            'Sample message',
            [],
            []
        );

        $handler->handle($record);

        $this->assertCount(1, MailService::$sent);
        $sent = MailService::$sent[0];
        $this->assertSame('dest@example.com', $sent['to']);
        $this->assertSame('Test Subject', $sent['subject']);
        $this->assertSame('html', $sent['format']);
    }
}
}
