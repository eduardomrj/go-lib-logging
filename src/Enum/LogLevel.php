<?php

declare(strict_types=1);

namespace GOlib\Log\Enum;

/**
 * Enum para os níveis de log padrão da PSR-3.
 *
 * Garante a consistência e a segurança de tipos ao registrar logs manuais.
 *
 * @version    1.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-30
 * @date       2025-08-30 11:50:00 (criação)
 */
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';
}
