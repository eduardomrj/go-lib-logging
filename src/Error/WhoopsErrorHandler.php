<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRun;
use Throwable;

/**
 * Tratador de Erros utilizando a biblioteca Whoops.
 *
 * Exibe uma página de erro interativa e detalhada, ideal para desenvolvimento,
 * incluindo o ID do evento no título da página.
 *
 * @version    4.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-11 22:21:00 (criação)
 * @date       2025-08-27 18:45:00 (alteração)
 */
class WhoopsErrorHandler implements ErrorHandlerInterface
{
    /**
     * Exibe a página de erro do Whoops com o ID do evento.
     *
     * @param Throwable $exception A exceção capturada.
     * @param string|null $uid O ID único do evento de log.
     */
    public function handle(Throwable $exception, ?string $uid = null): void
    {
        $whoops = new WhoopsRun();
        $handler = new PrettyPageHandler();

        if ($uid) {
            $handler->setPageTitle("Erro (ID: {$uid})");
        }

        $whoops->pushHandler($handler);
        $whoops->handleException($exception);
    }
}
