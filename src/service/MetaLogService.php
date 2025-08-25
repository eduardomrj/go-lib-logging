<?php

declare(strict_types=1);

namespace App\Lib\GOlib\Log\Service;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;

/**
 * Serviço de Meta-Log
 * * @version    16.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-12
 * @date       2025-08-12 11:50:00
 * @description Responsável por registrar o status de outras operações de log (ex: envio de email/discord)
 * de forma segura, escrevendo apenas no arquivo de log principal.
 */
class MetaLogService
{
    private static ?Logger $instance = null;

    /**
     * Cria e retorna uma instância de Logger configurada apenas com o File Handler.
     */
    private static function getFileLogger(): Logger
    {
        if (self::$instance === null) {
            // Cria um novo logger chamado 'meta-log' para diferenciá-lo nos arquivos de log.
            $logger = new Logger('meta-log');
            
            // Pega as configurações do file_handler do nosso .ini
            $fileConfig = ConfigService::get('file_handler', null, []);
            $logPath = $fileConfig['path'] ?? 'app/logs/serket.log';
            $logDays = (int) ($fileConfig['days'] ?? 14);

            $fileHandler = new RotatingFileHandler($logPath, $logDays, Level::Info);
            $fileHandler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"));
            
            $logger->pushHandler($fileHandler);
            self::$instance = $logger;
        }
        
        return self::$instance;
    }

    /**
     * Registra uma mensagem de log sobre o status de uma notificação.
     *
     * @param string $channel O canal da notificação (ex: 'discord', 'email').
     * @param string $message A mensagem a ser registrada.
     * @param array $context Contexto adicional.
     */
    public static function log(string $channel, string $message, array $context = []): void
    {
        // Só executa se o file_handler principal estiver ativado.
        $handlersConfig = ConfigService::get('monolog_handlers', null, []);
        if (filter_var($handlersConfig['file_handler_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $logger = self::getFileLogger();
            $logger->info("[{$channel}] " . $message, $context);
        }
    }
}
