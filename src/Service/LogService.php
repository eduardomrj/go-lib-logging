<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use GOlib\Log\Handler\AdiantiMailerHandler;
use GOlib\Log\Handler\RateLimitingDiscordHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\GroupHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\WebProcessor;
use Throwable;

/**
 * Serviço de Log (Singleton)
 * * @version    30.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-12
 * @date       2025-08-12 15:45:00
 * @description Configura e fornece uma instância única do Logger com handlers opcionais.
 */
class LogService
{
    private static ?LogService $instance = null;
    private Logger $logger;

    private function __construct()
    {
        $appName = ConfigService::get('general', 'application', 'SERKET');
        $this->logger = new Logger($appName);
        $this->logger->pushProcessor(new WebProcessor());
        $this->configureHandlers();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function configureHandlers(): void
    {
        $formatter = new LineFormatter(null, null, true, true);
        $handlersConfig = ConfigService::get('monolog_handlers', null, []);

        if (filter_var($handlersConfig['file_handler_enabled'] ?? true, FILTER_VALIDATE_BOOLEAN)) {
            $this->addFileHandler($formatter);
        }

        // --- LÓGICA REFEITA PARA MAIOR CLAREZA E CORREÇÃO ---

        // 1. Determina as configurações
        $logWarnings = filter_var($handlersConfig['log_warnings_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $useFingersCrossed = filter_var($handlersConfig['use_fingers_crossed'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $triggerLevel = Level::fromName($handlersConfig['notification_trigger_level'] ?? 'ERROR');

        // 2. Determina o nível mínimo para uma notificação ser enviada
        $notificationMinLevel = $triggerLevel;
        if ($logWarnings) {
            // Se o log de warnings está ativo, o nível mínimo para notificação é WARNING,
            // a menos que o trigger principal seja ainda mais baixo (ex: NOTICE).
            $notificationMinLevel = min(Level::Warning->value, $triggerLevel->value);
            $notificationMinLevel = Level::fromValue($notificationMinLevel);
        }

        // 3. Cria os handlers de notificação. Se usar FingersCrossed, eles precisam aceitar tudo (Debug).
        // Caso contrário, eles usam o nível mínimo calculado.
        $handlerCreationLevel = $useFingersCrossed ? Level::Debug : $notificationMinLevel;
        
        $notificationHandlers = [];
        if (filter_var($handlersConfig['discord_handler_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            if ($handler = $this->createDiscordHandler($formatter, $handlerCreationLevel)) {
                $notificationHandlers[] = $handler;
            }
        }
        if (filter_var($handlersConfig['email_handler_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            if ($handler = $this->createEmailHandler($formatter, $handlerCreationLevel)) {
                $notificationHandlers[] = $handler;
            }
        }

        if (empty($notificationHandlers)) {
            return;
        }

        // 4. Agrupa os handlers
        $handlerChain = new GroupHandler($notificationHandlers);

        // 5. Envolve com o DeduplicationHandler se o log de warnings estiver ativo
        if ($logWarnings) {
            $time = (int)($handlersConfig['deduplication_time'] ?? 3600);
            $handlerChain = new DeduplicationHandler($handlerChain, null, Level::Warning, $time, true);
        }

        // 6. Envolve com o FingersCrossedHandler se estiver ativo
        if ($useFingersCrossed) {
            $handlerChain = new FingersCrossedHandler($handlerChain, $triggerLevel);
        }

        $this->logger->pushHandler($handlerChain);
    }

    private function addFileHandler(LineFormatter $formatter): void
    {
        $fileConfig = ConfigService::get('file_handler', null, []);
        $logPath = $fileConfig['path'] ?? 'app/logs/serket.log';
        $logDays = (int) ($fileConfig['days'] ?? 14);
        $dir = dirname($logPath);
        if (!is_dir($dir) || !is_writable($dir)) {
            error_log('[go-lib-logging] Diretório de log inacessível: ' . $dir);
            return;
        }

        $fileHandler = new RotatingFileHandler($logPath, $logDays, Level::Debug);
        $fileHandler->setFormatter($formatter);
        $this->logger->pushHandler($fileHandler);
    }

    private function createDiscordHandler(LineFormatter $formatter, Level $level): ?RateLimitingDiscordHandler
    {
        $discordConfig = ConfigService::get('discord_handler', null, []);
        $discordUrl = $discordConfig['webhook_url'] ?? null;
        if ($discordUrl) {
            $maxPerMinute = (int) ($discordConfig['max_per_minute'] ?? 6);
            $discordHandler = new RateLimitingDiscordHandler($discordUrl, $maxPerMinute, $level);
            $discordHandler->setFormatter($formatter);
            return $discordHandler;
        }
        return null;
    }

    private function createEmailHandler(LineFormatter $formatter, Level $level): ?AdiantiMailerHandler
    {
        $emailConfig = ConfigService::get('email_handler', null, []);
        if (!empty($emailConfig['to_address'])) {
            $emailHandler = new AdiantiMailerHandler($emailConfig, $level);
            $emailHandler->setFormatter($formatter);
            return $emailHandler;
        }
        return null;
    }

    public function getLogger(): Logger
    {
        return $this->logger;
    }

    public static function logAndThrow(Throwable $e): void
    {
        LogService::getInstance()->getLogger()->critical($e->getMessage(), [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
