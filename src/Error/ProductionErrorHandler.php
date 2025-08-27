<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Adianti\Widget\Dialog\TMessage;
use Throwable;

/**
 * Tratador de Erros para o Ambiente de Produção.
 *
 * Exibe uma mensagem amigável para o usuário, sem expor detalhes técnicos,
 * mas fornecendo um código de erro único para facilitar o suporte.
 *
 * @version    4.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-11 22:21:00 (criação)
 * @date       2025-08-27 18:40:00 (alteração)
 */
class ProductionErrorHandler implements ErrorHandlerInterface
{
    /**
     * Exibe a mensagem de erro amigável com o ID do evento.
     *
     * @param Throwable $exception A exceção capturada.
     * @param string|null $uid O ID único do evento de log.
     */
    public function handle(Throwable $exception, ?string $uid = null): void
    {
        $message = "Ocorreu um erro inesperado e nossa equipe já foi notificada.<br><br>" .
            "Para agilizar seu atendimento, por favor, informe ao suporte o seguinte código de erro:<br><br>" .
            "<div style='font-weight:bold; font-size:1.2em; user-select:all;'>{$uid}</div>";

        new TMessage(
            'error',
            $message,
            null,
            'Ops! Algo deu errado'
        );
    }
}
