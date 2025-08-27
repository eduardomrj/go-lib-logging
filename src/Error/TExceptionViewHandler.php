<?php

declare(strict_types=1);

namespace GOlib\Log\Error;

use GOlib\Log\Contracts\ErrorHandlerInterface;
use Adianti\Widget\Util\TExceptionView;
use Throwable;

/**
 * Tratador de Erros utilizando o TExceptionView do Adianti.
 *
 * Exibe a pilha de erros completa, adicionando o ID único do evento
 * no título para fácil correlação com os logs.
 *
 * @version    4.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-11 22:21:00 (criação)
 * @date       2025-08-27 18:40:00 (alteração)
 */
class TExceptionViewHandler implements ErrorHandlerInterface
{
    /**
     * Exibe a TExceptionView com o ID do evento no título.
     *
     * @param Throwable $exception A exceção capturada.
     * @param string|null $uid O ID único do evento de log.
     */
    public function handle(Throwable $exception, ?string $uid = null): void
    {
        // Adiciona o ID ao título da janela de exceção
        if ($uid) {
            $exception->title = "Erro (ID: {$uid})";
        }

        new TExceptionView($exception);
    }
}
