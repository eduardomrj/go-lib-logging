<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use GOlib\Log\Contracts\MetadataAgentInterface;

/**
 * Serviço de Meta-Log.
 *
 * Responsável por registrar o status de outras operações de log (ex: envio de
 * email/discord) de forma segura. Agora é extensível através de "Agentes de Metadados".
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-12 11:50:00 (criação)
 * @date       2025-08-26 10:35:00 (alteração)
 */
class MetaLogService
{
    private ?Logger $logger = null;
    
    /** @var MetadataAgentInterface[] */
    private array $agents = [];

    /**
     * Construtor do MetaLogService.
     *
     * @param ConfigService $configService O serviço de configuração injetado.
     */
    public function __construct(private ConfigService $configService)
    {
    }

    /**
     * Adiciona um agente de metadados para enriquecer os logs.
     *
     * @param MetadataAgentInterface $agent O agente a ser adicionado.
     */
    public function addAgent(MetadataAgentInterface $agent): void
    {
        $this->agents[] = $agent;
    }

    /**
     * Registra uma mensagem de log sobre o status de uma notificação.
     *
     * @param string $channel O canal da notificação (ex: 'discord', 'email').
     * @param string $message A mensagem a ser registrada.
     * @param array<string, mixed> $context Contexto adicional.
     */
    public function log(string $channel, string $message, array $context = []): void
    {
        if (!filter_var($this->configService->get('monolog_handlers', 'file_handler_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $metadata = [];
        foreach ($this->agents as $agent) {
            $metadata = array_merge($metadata, $agent->getMetadata());
        }

        $finalContext = array_merge($context, $metadata);

        $this->getFileLogger()->info("[{$channel}] " . $message, $finalContext);
    }

    /**
     * Cria e retorna uma instância de Logger configurada apenas com o File Handler.
     * A instância é criada apenas uma vez (lazy loading).
     *
     * @return Logger
     */
    private function getFileLogger(): Logger
    {
        if ($this->logger === null) {
            $this->logger = new Logger('meta-log');
            
            $fileConfig = $this->configService->get('file_handler', null, []);
            $logPath = $fileConfig['path'] ?? 'app/logs/go-lib-meta.log';
            $logDays = (int) ($fileConfig['days'] ?? 7);
            
            $dir = dirname($logPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            if (is_writable($dir)) {
                $fileHandler = new RotatingFileHandler($logPath, $logDays, Level::Info);
                $fileHandler->setFormatter(new LineFormatter("[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n"));
                $this->logger->pushHandler($fileHandler);
            } else {
                error_log('[go-lib-logging] Diretório de meta-log sem permissão de escrita: ' . $dir);
            }
        }
        
        return $this->logger;
    }
}
