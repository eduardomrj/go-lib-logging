<?php

declare(strict_types=1);

namespace GOlib\Log\Processor;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Processador do Monolog que adiciona um identificador único a cada registro de log.
 *
 * O ID é gerado uma vez por requisição e anexado a todos os logs,
 * permitindo correlacionar eventos que ocorreram no mesmo processo.
 * O formato do ID é XXXX-XXXX para facilitar a leitura.
 *
 * @version    1.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-27 18:10:00 (criação)
 */
class UidProcessor implements ProcessorInterface
{
    private string $uid;

    public function __construct()
    {
        // Gera um ID alfanumérico de 8 caracteres
        $randomPart = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 8)), 0, 8);
        // Formata o ID com um hífen no meio
        $this->uid = substr($randomPart, 0, 4) . '-' . substr($randomPart, 4, 4);
    }

    /**
     * Adiciona o ID único ao registro de log.
     *
     * @param LogRecord $record O registro de log.
     * @return LogRecord O registro de log modificado.
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $record->extra['uid'] = $this->uid;
        return $record;
    }
}
