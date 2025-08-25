<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Throwable;
use ErrorException;
use GOlib\Log\Service\LogService;

final class ErrorHandlerSetup
{
    /**
     * Registra os tratadores globais de erros e exceções.
     */
    public static function register(): void
    {
        $handler = ErrorHandlerFactory::create();

        set_exception_handler([$handler, 'handle']);
        set_error_handler(
            static function (int $errno, string $errstr, string $errfile, int $errline) use ($handler): bool {
                $handler->handle(new ErrorException($errstr, 0, $errno, $errfile, $errline));
                return true;
            }
        );
    }

    /**
     * Registra a exceção no log configurado e a encaminha para o tratador visual.
     */
    public static function handleException(Throwable $exception): void
    {
        // 1. Loga a exceção com contexto completo (arquivo, linha e trace).
        //    Dessa forma, todos os handlers definidos no logging.ini (arquivo, Discord, e-mail)
        //    serão disparados de acordo com o nível configurado.
        $logger = LogService::getInstance()->getLogger();
        $logger->critical(
            $exception->getMessage(),
            [
                'file'  => $exception->getFile(),
                'line'  => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]
        );

        // 2. Encaminha a exceção para o tratador visual apropriado (Whoops, TExceptionView ou Production).
        $handler = ErrorHandlerFactory::create();
        $handler->handle($exception);
    }
}
