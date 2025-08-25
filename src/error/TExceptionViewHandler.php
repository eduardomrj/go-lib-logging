<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Adianti\Widget\Util\TExceptionView;

/**
 * Tratador de Erros utilizando o TExceptionView do Adianti
 * * @version    3.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-11
 * @date       2025-08-11 22:21:00
 * @description Exibe a pilha de erros completa usando o componente visual padrão do framework.
 */
class TExceptionViewHandler implements ErrorHandlerInterface
{
    public function handle(\Throwable $exception): void
    {
        new TExceptionView($exception);
    }
}
