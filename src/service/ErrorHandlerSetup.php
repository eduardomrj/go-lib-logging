<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use ErrorException;
use Throwable;

/**
 * Configura os tratadores de erros e exceções da aplicação.
 *
 * @version    1.0.0
 */
class ErrorHandlerSetup
{
    /**
     * Registra os tratadores globais de erros e exceções.
     */
    public static function register(): void
    {
        $handler = ErrorHandlerFactory::create();

        set_exception_handler([$handler, 'handle']);

        set_error_handler(static function (int $errno, string $errstr, string $errfile, int $errline) use ($handler): bool {
            $handler->handle(new ErrorException($errstr, 0, $errno, $errfile, $errline));
            return true;
        });
    }

    /**
     * Encaminha a exceção para o tratador configurado.
     */
    public static function handleException(Throwable $exception): void
    {
        $handler = ErrorHandlerFactory::create();
        $handler->handle($exception);
    }
}
