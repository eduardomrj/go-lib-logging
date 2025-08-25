<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as WhoopsRun;

/**
 * Tratador de Erros utilizando a biblioteca Whoops
 * * @version    3.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-11
 * @date       2025-08-11 22:21:00
 * @description Exibe uma página de erro interativa e detalhada, ideal para desenvolvimento.
 */
class WhoopsErrorHandler implements ErrorHandlerInterface
{
    public function handle(\Throwable $exception): void
    {
        $whoops = new WhoopsRun();
        $whoops->pushHandler(new PrettyPageHandler());
        $whoops->handleException($exception);
    }
}
