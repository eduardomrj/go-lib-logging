<?php

declare(strict_types=1);

namespace GOlib\Log\Handler;

use Monolog\Level;
use Monolog\LogRecord;
use Adianti\Registry\TSession;
use GOlib\Log\Contracts\CacheInterface;
use GOlib\Log\Service\MetaLogService;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Handler customizado do Monolog para enviar logs para o Discord com Rate Limit.
 *
 * @version    19.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-12 11:55:00 (criação)
 * @date       2025-08-27 18:20:00 (alteração)
 */
class RateLimitingDiscordHandler extends AbstractProcessingHandler
{
    public function __construct(
        private string $webhookUrl,
        private int $maxPerMinute,
        private CacheInterface $cache,
        private MetaLogService $metaLogService,
        int|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->isRateLimited()) {
            $this->send($this->buildEmbed($record));
        } else {
            $this->metaLogService->log('discord', 'Notificação para o Discord bloqueada por Rate Limit.', ['uid' => $record->extra['uid'] ?? 'N/A']);
        }
    }

    private function send(array $payload): void
    {
        if (str_starts_with($this->webhookUrl, 'https://example.com')) {
            $this->metaLogService->log('discord', 'Notificação enviada com sucesso (modo de teste).', ['http_code' => 204]);
            return;
        }

        $jsonPayload = json_encode($payload);
        $ch = curl_init($this->webhookUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => 1,
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            $this->metaLogService->log('discord', 'Notificação enviada com sucesso.', ['http_code' => $httpCode]);
        } else {
            $this->metaLogService->log('discord', 'Falha ao enviar notificação.', [
                'http_code' => $httpCode,
                'curl_error' => $error,
                'response_body' => $response
            ]);
        }
    }

    private function buildEmbed(LogRecord $record): array
    {
        $context = $record->context;
        $levelColor = $this->getLevelColor($record->level);
        $levelName = $record->level->getName();
        $iconUrl = $this->getLevelIcon($record->level);
        $uid = $record->extra['uid'] ?? 'N/A';

        $description = '';
        if (!empty($context['trace'])) {
            $traceLines = explode("\n", $context['trace']);
            $description = "```\n" . implode("\n", array_slice($traceLines, 0, 5)) . "\n...```";
        }

        $fields = [
            [
                'name' => '🆔 ID do Evento',
                'value' => "`{$uid}`",
                'inline' => true,
            ],
            [
                'name' => 'Nível',
                'value' => $levelName,
                'inline' => true,
            ],
            [
                'name' => 'Arquivo',
                'value' => "`" . ($context['file'] ?? 'N/A') . "`",
                'inline' => false,
            ],
            [
                'name' => 'Linha',
                'value' => "`" . ($context['line'] ?? 'N/A') . "`",
                'inline' => true,
            ],
            [
                'name' => 'IP do Usuário',
                'value' => $_SERVER['REMOTE_ADDR'] ?? 'N/A',
                'inline' => true,
            ],
        ];

        if (TSession::getValue('logged')) {
            $userInfo = "**ID:** `" . TSession::getValue('userid') . "`\n" .
                "**Login:** `" . TSession::getValue('login') . "`\n" .
                "**Nome:** `" . TSession::getValue('username') . "`\n" .
                "**Email:** `" . TSession::getValue('usermail') . "`";
            $fields[] = ['name' => '👤 Usuário Logado', 'value' => $userInfo, 'inline' => false];
        } else {
            $fields[] = ['name' => '👤 Sessão', 'value' => 'Nenhum usuário logado no momento do erro.', 'inline' => false];
        }

        return [
            'embeds' => [[
                'title' => "🚨 " . mb_substr($record->message, 0, 250),
                'description' => $description,
                'color' => $levelColor,
                'author' => ['name' => $_SERVER['SERVER_NAME'] ?? 'localhost', 'icon_url' => $iconUrl],
                'fields' => $fields,
                'footer' => ['text' => 'Horário do Erro'],
                'timestamp' => $record->datetime->format('c'),
            ]]
        ];
    }

    private function getLevelColor(Level $level): int
    {
        return match ($level) {
            Level::Debug, Level::Info => 3447003, // Azul
            Level::Notice, Level::Warning => 15105642, // Laranja
            Level::Error => 15158332, // Vermelho
            Level::Critical, Level::Alert, Level::Emergency => 15548997, // Vermelho Escuro
            default => 9807270, // Cinza
        };
    }

    private function getLevelIcon(Level $level): string
    {
        return match ($level) {
            Level::Debug, Level::Info => 'https://i.imgur.com/v3iZ42u.png', // Info
            Level::Notice, Level::Warning => 'https://i.imgur.com/P4OUPdY.png', // Warning
            Level::Error, Level::Critical, Level::Alert, Level::Emergency => 'https://i.imgur.com/t1hB5G7.png', // Error
            default => 'https://i.imgur.com/eB32Ssw.png', // Default
        };
    }

    private function isRateLimited(): bool
    {
        $cacheKey = 'discord_log_timestamps';
        $now = time();

        $timestamps = $this->cache->get($cacheKey, []);

        $timestamps = array_filter($timestamps, fn($ts) => $now - $ts < 60);

        if (count($timestamps) >= $this->maxPerMinute) {
            return true;
        }

        $timestamps[] = $now;
        $this->cache->set($cacheKey, $timestamps, 65);

        return false;
    }
}
