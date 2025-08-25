<?php

declare(strict_types=1);

namespace App\Lib\GOlib\Log\Handler;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Adianti\Registry\TSession;
use App\Lib\GOlib\Log\Service\MetaLogService;
use Exception;

/**
 * Handler para enviar logs por e-mail usando a classe TMail do Adianti Framework.
 * * @version    19.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-12
 * @date       2025-08-12 12:05:00
 * @description Formata o log como um e-mail HTML e o envia via SMTP.
 */
class AdiantiMailerHandler extends AbstractProcessingHandler
{
    private array $config;

    public function __construct(array $config, int|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->config = $config;
        parent::__construct($level, $bubble);
    }

    /**
     * Envia o e-mail com o log formatado.
     */
    protected function write(LogRecord $record): void
    {
        try {
            // A classe TMail é carregada do escopo global (app/lib/util/)
            $mail = new \TMail;
            $mail->setFrom($this->config['from_address'], $this->config['from_name']);
            $mail->addAddress($this->config['to_address']);
            $mail->setSubject($this->config['subject']);
            $mail->setHtmlBody($this->buildHtmlBody($record));
            
            if (!empty($this->config['smtp_auth'])) {
                $mail->setSmtpHost($this->config['smtp_host'], $this->config['smtp_port']);
                $mail->setSmtpUser($this->config['smtp_user'], $this->config['smtp_pass']);
                if (isset($this->config['smtp_secure'])) {
                    $mail->setSmtpSecure($this->config['smtp_secure']);
                }
            }

            $mail->send();
            MetaLogService::log('email', 'Notificação enviada com sucesso via TMail.', ['to' => $this->config['to_address']]);

        } catch (Exception $e) {
            MetaLogService::log('email', 'Falha ao enviar notificação via TMail.', [
                'to' => $this->config['to_address'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Constrói o corpo do e-mail em formato HTML.
     */
    private function buildHtmlBody(LogRecord $record): string
    {
        $context = $record->context;
        $levelName = $record->level->getName();
        $levelColor = $this->getLevelColor($record->level);

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
