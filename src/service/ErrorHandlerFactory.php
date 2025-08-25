<?php

declare(strict_types=1);

namespace App\Lib\GOlib\Log\Service;

use App\Lib\GOlib\Log\Contracts\ErrorHandlerInterface;
use App\Lib\GOlib\Log\Error\ProductionErrorHandler;
use App\Lib\GOlib\Log\Error\TExceptionViewHandler;
use App\Lib\GOlib\Log\Error\WhoopsErrorHandler;

/**
 * Factory para criar o Tratador de Erros (ErrorHandler) apropriado
 * * @version    10.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-12
 * @date       2025-08-12 11:15:00
 * @description Utiliza o padrão Factory Method para decidir qual estratégia de erro instanciar.
 */
class ErrorHandlerFactory
{
    public static function create(): ErrorHandlerInterface
    {
        $environment = ConfigService::get('logging', 'environment', 'development');
        
        if ($environment === 'development') {
            // Lógica para o ambiente de DESENVOLVIMENTO
            $useWhoops = filter_var(ConfigService::get('logging', 'whoops', false), FILTER_VALIDATE_BOOLEAN);
            
            if ($useWhoops) {
                return new WhoopsErrorHandler();
            } else {
                return new TExceptionViewHandler();
            }
        }
        else { // $environment === 'production'
            // Lógica para o ambiente de PRODUÇÃO
            $isDebug = filter_var(ConfigService::get('general', 'debug', false), FILTER_VALIDATE_BOOLEAN);

            if ($isDebug) {
                return new TExceptionViewHandler();
            } else {
                return new ProductionErrorHandler();
            }
        }
    }
}
