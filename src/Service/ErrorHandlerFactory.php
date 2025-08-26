<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use GOlib\Log\Error\WhoopsErrorHandler;
use GOlib\Log\Error\TExceptionViewHandler;
use GOlib\Log\Error\ProductionErrorHandler;
use GOlib\Log\Contracts\ErrorHandlerInterface;

/**
 * Factory para criar o Tratador de Erros (ErrorHandler) apropriado.
 *
 * Utiliza o padrão Factory Method para decidir qual estratégia de erro
 * instanciar com base nas configurações injetadas.
 *
 * @version    11.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-12 11:15:00 (criação)
 * @date       2025-08-26 10:55:00 (alteração)
 */
class ErrorHandlerFactory
{
    /**
     * Construtor da ErrorHandlerFactory.
     *
     * @param ConfigService $configService O serviço de configuração.
     */
    public function __construct(private ConfigService $configService)
    {
    }

    /**
     * Cria e retorna a instância do tratador de erros apropriado.
     *
     * @return ErrorHandlerInterface
     */
    public function create(): ErrorHandlerInterface
    {
        $environment = $this->configService->get('logging', 'environment', 'development');
        
        if ($environment === 'development') {
            return $this->createDevelopmentHandler();
        }
        
        return $this->createProductionHandler();
    }

    /**
     * Cria um tratador de erros para o ambiente de desenvolvimento.
     *
     * @return ErrorHandlerInterface
     */
    private function createDevelopmentHandler(): ErrorHandlerInterface
    {
        $useWhoops = filter_var($this->configService->get('logging', 'whoops', false), FILTER_VALIDATE_BOOLEAN);
        
        if ($useWhoops) {
            return new WhoopsErrorHandler();
        }
        
        return new TExceptionViewHandler();
    }

    /**
     * Cria um tratador de erros para o ambiente de produção.
     *
     * @return ErrorHandlerInterface
     */
    private function createProductionHandler(): ErrorHandlerInterface
    {
        $isDebug = filter_var($this->configService->get('general', 'debug', false), FILTER_VALIDATE_BOOLEAN);

        if ($isDebug) {
            return new TExceptionViewHandler();
        }
        
        return new ProductionErrorHandler();
    }
}
