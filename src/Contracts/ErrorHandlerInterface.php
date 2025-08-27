<?php

declare(strict_types=1);

namespace GOlib\Log\Contracts;

use Throwable;

/**
 * Interface para Tratadores de Erro (Error Handlers).
 *
 * Define o contrato que todas as estratégias de exibição de erro devem seguir.
 *
 * @version    4.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-11 22:21:00 (criação)
 * @date       2025-08-27 18:45:00 (alteração)
 */
interface ErrorHandlerInterface
{
    /**
     * Processa e exibe uma exceção.
     *
     * @param Throwable $exception A exceção ou erro capturado.
     * @param string|null $uid O ID único do evento de log associado.
     */
    public function handle(Throwable $exception, ?string $uid = null): void;
}
