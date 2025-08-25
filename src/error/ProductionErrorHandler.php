<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Adianti\Widget\Dialog\TMessage;

/**
 * Tratador de Erros para o Ambiente de Produção
 * * @version    3.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-11
 * @date       2025-08-11 22:21:00
 * @description Exibe uma mensagem genérica de erro, sem expor detalhes técnicos.
 */
class ProductionErrorHandler implements ErrorHandlerInterface
{
    public function handle(\Throwable $exception): void
    {
        new TMessage(
            'error',
            'Ocorreu um erro inesperado. Nossa equipe já foi notificada.',
            null,
            'Erro Interno'
        );
    }
}
