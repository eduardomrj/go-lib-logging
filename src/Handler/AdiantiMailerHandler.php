<?php

declare(strict_types=1);

namespace GOlib\Log\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Adianti\Registry\TSession;
use GOlib\Log\Service\MetaLogService;
use Exception;
use MailService; // Usa a classe MailService do template Adianti

/**
 * Handler para enviar logs por e-mail usando a classe MailService.
 *
 * Formata o log como um e-mail HTML e o envia usando as configurações de
 * SMTP do sistema, registrando o status do envio via MetaLogService.
 *
 * @version    23.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-12 13:54:00 (criação)
 * @date       2025-08-27 18:25:00 (alteração)
 */
class AdiantiMailerHandler extends AbstractProcessingHandler
{
    public function __construct(
        private array $config,
        private MetaLogService $metaLogService,
        int|Level $level = Level::Debug,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
    }

    /**
     * {@inheritdoc}
     */
    protected function write(LogRecord $record): void
    {
        try {
            $to      = $this->config['to_address'];
            $subject = $this->config['subject'];
            $body    = $this->buildHtmlBody($record);

            MailService::send($to, $subject, $body, 'html');

            $this->metaLogService->log('email', 'Notificação enviada com sucesso via MailService.', ['to' => $to, 'uid' => $record->extra['uid'] ?? 'N/A']);
        } catch (Exception $e) {
            $this->metaLogService->log('email', 'Falha ao enviar notificação via MailService.', [
                'to' => $this->config['to_address'],
                'error' => $e->getMessage(),
                'uid' => $record->extra['uid'] ?? 'N/A'
            ]);
        }
    }

    /**
     * Constrói o corpo do e-mail em formato HTML.
     *
     * @param LogRecord $record O registro de log.
     * @return string O corpo do e-mail em HTML.
     */
    private function buildHtmlBody(LogRecord $record): string
    {
        $context = $record->context;
        $levelName = $record->level->getName();
        $levelColor = $this->getLevelColor($record->level);
        $uid = $record->extra['uid'] ?? 'N/A';

        $html = "<!DOCTYPE html><html><head><style>" .
            "body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }" .
            ".container { background-color: #ffffff; border: 1px solid #dddddd; max-width: 800px; margin: auto; padding: 20px; }" .
            ".header { background-color: {$levelColor}; color: white; padding: 10px; text-align: center; font-size: 20px; }" .
            "table { width: 100%; border-collapse: collapse; margin-top: 20px; }" .
            "th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }" .
            "th { background-color: #f2f2f2; width: 150px; }" .
            "pre { background-color: #eeeeee; padding: 10px; border: 1px solid #cccccc; white-space: pre-wrap; word-wrap: break-word; }" .
            "</style></head><body>";

        $html .= "<div class='container'>";
        $html .= "<div class='header'>🚨 Notificação de Erro: {$levelName} 🚨</div>";
        $html .= "<table>";
        $html .= "<tr><th>ID do Evento</th><td><b>{$uid}</b></td></tr>";
        $html .= "<tr><th>Mensagem</th><td>" . htmlspecialchars($record->message) . "</td></tr>";
        $html .= "<tr><th>Arquivo</th><td>" . ($context['file'] ?? 'N/A') . "</td></tr>";
        $html .= "<tr><th>Linha</th><td>" . ($context['line'] ?? 'N/A') . "</td></tr>";
        $html .= "<tr><th>Servidor</th><td>" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "</td></tr>";
        $html .= "<tr><th>IP</th><td>" . ($_SERVER['REMOTE_ADDR'] ?? 'N/A') . "</td></tr>";

        if (TSession::getValue('logged')) {
            $html .= "<tr><th colspan='2' style='background-color: #e0e0e0;'>Informações do Usuário</th></tr>";
            $html .= "<tr><th>ID</th><td>" . TSession::getValue('userid') . "</td></tr>";
            $html .= "<tr><th>Login</th><td>" . TSession::getValue('login') . "</td></tr>";
            $html .= "<tr><th>Nome</th><td>" . TSession::getValue('username') . "</td></tr>";
            $html .= "<tr><th>Email</th><td>" . TSession::getValue('usermail') . "</td></tr>";
        }

        $html .= "</table>";

        if (!empty($context['trace'])) {
            $html .= "<h3>Stack Trace:</h3>";
            $html .= "<pre>" . htmlspecialchars($context['trace']) . "</pre>";
        }

        $html .= "</div></body></html>";

        return $html;
    }

    /**
     * Retorna uma cor em formato hexadecimal baseada no nível do log.
     *
     * @param Level $level O nível do log.
     * @return string A cor em hexadecimal.
     */
    private function getLevelColor(Level $level): string
    {
        return match ($level) {
            Level::Debug, Level::Info => '#3498db', // Azul
            Level::Notice, Level::Warning => '#f39c12', // Laranja
            Level::Error => '#e74c3c', // Vermelho
            Level::Critical, Level::Alert, Level::Emergency => '#c0392b', // Vermelho Escuro
            default => '#95a5a6', // Cinza
        };
    }
}
