<?php

declare(strict_types=1);

namespace GOlib\Log\Facade;

use Throwable;
use GOlib\Log\Enum\LogLevel;
use Adianti\Registry\TRegistry;
use Monolog\Logger as MonologLogger;

/**
 * Facade para registro manual de logs.
 *
 * Fornece uma interface estática e simplificada para registrar exceções
 * tratadas manualmente em blocos try-catch, utilizando um enum para
 * garantir a segurança de tipos nos níveis de log.
 *
 * @version    1.1.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-30
 * @date       2025-08-30 11:52:00 (alteração)
 */
class GOLog
{
    /**
     * Registra uma exceção ou erro no sistema de log principal com uma única chamada.
     *
     * @param Throwable $e A exceção ou erro a ser logado.
     * @param LogLevel $level O nível do log, utilizando o enum LogLevel. Padrão é ERROR.
     * @param string|null $customMessage Uma mensagem customizada para preceder a mensagem da exceção.
     */
    public static function log(Throwable $e, LogLevel $level = LogLevel::ERROR, ?string $customMessage = 'Erro de negócio tratado'): void
    {
        try {
            /** @var MonologLogger|null $logger */
            $logger = TRegistry::get('golib.logger');

            if ($logger instanceof MonologLogger) {
                $message = $customMessage ? $customMessage . ': ' . $e->getMessage() : $e->getMessage();

                $context = [
                    'file'  => $e->getFile(),
                    'line'  => $e->getLine(),
                    'code'  => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ];

                // Usa o valor do enum ('error', 'warning', etc.) para chamar o método do logger
                $levelValue = $level->value;
                if (method_exists($logger, $levelValue)) {
                    $logger->{$levelValue}($message, $context);
                } else {
                    $logger->error($message, $context); // Fallback para 'error'
                }
            }
        } catch (Throwable $logException) {
            // Se o sistema de log principal falhar, usa o log de erro padrão do PHP.
            error_log("Falha ao registrar log via GOLog Facade: " . $logException->getMessage());
            error_log("Erro original que tentou ser logado: " . $e->getMessage());
        }
    }
}

