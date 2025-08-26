<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;

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

    private static function ensureWritableDir(string $dir): string
    {
        // Se já existe e é gravável, ok
        if (is_dir($dir) && is_writable($dir)) {
            return $dir;
        }

        // Tenta criar (com recursão) e trata condição de corrida
        if (!is_dir($dir) && @mkdir($dir, 0775, true) && is_dir($dir)) {
            return $dir;
        }

        // Fallback para um diretório no /tmp
        $fallback = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'go-lib-logging';
        if (!is_dir($fallback)) {
            @mkdir($fallback, 0775, true);
        }
        return $fallback;
    }

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
            $logPath = $fileConfig['path'] ?? GO_LIB_LOG_DEFAULT_FILE;
            $logDays = (int) ($fileConfig['days'] ?? 14);
            $dir = dirname($logPath);

            $dir = self::ensureWritableDir($dir);

            // Recalcula caminho do arquivo no diretório garantido
            $logPath = $dir . DIRECTORY_SEPARATOR . basename($logPath);

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
