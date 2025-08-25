<?php

declare(strict_types=1);

namespace GOlib\Log\Contracts;

/**
 * Interface para Tratadores de Erro (Error Handlers)
 * * @version    3.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-11
 * @date       2025-08-11 22:21:00
 * @description Define o contrato que todas as estratégias de exibição de erro devem seguir.
 */
interface ErrorHandlerInterface
{
    public function handle(\Throwable $exception): void;
}
