<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Throwable;
use Monolog\Level;
use Monolog\Logger;
use GOlib\Log\Processor\UidProcessor; // Importa o novo processador
use Monolog\Handler\GroupHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\FingersCrossedHandler;
use GOlib\Log\Handler\AdiantiMailerHandler;
use GOlib\Log\Handler\RateLimitingDiscordHandler;
use GOlib\Log\Cache\FileCache;

/**
 * Serviço de Log principal.
 *
 * Configura e fornece uma instância do Logger com handlers baseados
 * nas configurações fornecidas. Esta versão utiliza injeção de dependência.
 *
 * @version    32.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-12 15:45:00 (criação)
 * @date       2025-08-27 18:15:00 (alteração)
 */
class LogService
{
    private Logger $logger;

    /**
     * Construtor do LogService.
     *
     * @param ConfigService $configService Serviço de configuração.
     * @param MetaLogService $metaLogService Serviço de meta-log para registrar status de notificação.
     */
    public function __construct(
        private ConfigService $configService,
        private MetaLogService $metaLogService
    ) {
        $appName = $this->configService->get('general', 'application', 'GOLIB-APP');
        $this->logger = new Logger($appName);

        // Anexa os processadores globais
        $this->logger->pushProcessor(new WebProcessor());
        $this->logger->pushProcessor(new UidProcessor()); // Adiciona o nosso processador de ID único

        $this->configureHandlers();
    }

    /**
     * Configura os handlers do Monolog com base no arquivo .ini.
     */
    private function configureHandlers(): void
    {
        // O formato padrão agora não inclui o ID, para não poluir handlers visuais.
        $defaultFormatter = new LineFormatter(null, null, true, true);

        if (filter_var($this->configService->get('file_handler', 'enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            $this->addFileHandler(); // O formato customizado será criado dentro deste método
        }

        $notificationHandlers = $this->buildNotificationHandlers($defaultFormatter);

        if (empty($notificationHandlers)) {
            return;
        }

        $handlerChain = new GroupHandler($notificationHandlers);

        $strategyConfig = $this->configService->get('notification_strategy', null, []);

        if (filter_var($strategyConfig['log_warnings_enabled'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $time = (int)($strategyConfig['deduplication_time'] ?? 300);
            $handlerChain = new DeduplicationHandler($handlerChain, null, Level::Warning, $time, true);
        }

        if (filter_var($strategyConfig['use_fingers_crossed'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            $triggerLevelName = $strategyConfig['trigger_level'] ?? 'ERROR';
            $triggerLevel = Level::fromName($triggerLevelName);
            $handlerChain = new FingersCrossedHandler($handlerChain, $triggerLevel);
        }

        $this->logger->pushHandler($handlerChain);
    }

    /**
     * Constrói e retorna um array com os handlers de notificação (Email, Discord) ativos.
     *
     * @param LineFormatter $formatter
     * @return array
     */
    private function buildNotificationHandlers(LineFormatter $formatter): array
    {
        $notificationHandlers = [];

        if (filter_var($this->configService->get('discord_handler', 'enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            if ($handler = $this->createDiscordHandler($formatter)) {
                $notificationHandlers[] = $handler;
            }
        }
        if (filter_var($this->configService->get('email_handler', 'enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            if ($handler = $this->createEmailHandler($formatter)) {
                $notificationHandlers[] = $handler;
            }
        }
        return $notificationHandlers;
    }

    /**
     * Adiciona o handler de log em arquivo com o formato de ID único.
     */
    private function addFileHandler(): void
    {
        $fileConfig = $this->configService->get('file_handler', null, []);
        $logPath = $fileConfig['path'] ?? 'files/logs/logging.log';
        $logDays = (int) ($fileConfig['days'] ?? 14);
        $levelName = $fileConfig['level'] ?? 'DEBUG';
        $level = Level::fromName($levelName);

        $dir = dirname($logPath);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            error_log('[' . $this->logger->getName() . '] Falha ao criar diretório de log: ' . $dir);
            return;
        }
        if (!is_writable($dir)) {
            error_log('[' . $this->logger->getName() . '] Diretório de log sem permissão de escrita: ' . $dir);
            return;
        }

        // Cria um formatador específico para o arquivo de log que inclui o ID único.
        $fileFormatter = new LineFormatter(
            "[%datetime%] [%extra.uid%] %channel%.%level_name%: %message% %context%\n",
            null,
            true,
            true
        );

        $fileHandler = new RotatingFileHandler($logPath, $logDays, $level);
        $fileHandler->setFormatter($fileFormatter);
        $this->logger->pushHandler($fileHandler);
    }

    /**
     * Cria uma instância do RateLimitingDiscordHandler.
     *
     * @param LineFormatter $formatter
     * @return RateLimitingDiscordHandler|null
     */
    private function createDiscordHandler(LineFormatter $formatter): ?RateLimitingDiscordHandler
    {
        $discordConfig = $this->configService->get('discord_handler', null, []);
        if (empty($discordConfig['webhook_url'])) {
            return null;
        }

        $levelName = $discordConfig['level'] ?? 'ERROR';
        $level = Level::fromName($levelName);
        $maxPerMinute = (int) ($discordConfig['max_per_minute'] ?? 6);

        $cache = new FileCache();
        $discordHandler = new RateLimitingDiscordHandler($discordConfig['webhook_url'], $maxPerMinute, $cache, $this->metaLogService, $level);
        $discordHandler->setFormatter($formatter);

        return $discordHandler;
    }

    /**
     * Cria uma instância do AdiantiMailerHandler.
     *
     * @param LineFormatter $formatter
     * @return AdiantiMailerHandler|null
     */
    private function createEmailHandler(LineFormatter $formatter): ?AdiantiMailerHandler
    {
        $emailConfig = $this->configService->get('email_handler', null, []);
        if (empty($emailConfig['to_address'])) {
            return null;
        }

        $levelName = $emailConfig['level'] ?? 'ERROR';
        $level = Level::fromName($levelName);

        $emailHandler = new AdiantiMailerHandler($emailConfig, $this->metaLogService, $level);
        $emailHandler->setFormatter($formatter);

        return $emailHandler;
    }

    /**
     * Retorna a instância do Logger configurado.
     *
     * @return Logger
     */
    public function getLogger(): Logger
    {
        return $this->logger;
    }
}
