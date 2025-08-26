<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Throwable;
use ErrorException;

/**
 * Orquestra a configuração e registro dos tratadores de erros e exceções.
 *
 * Esta classe centraliza a inicialização do sistema de log, instanciando
 * e conectando os serviços necessários (LogService, ErrorHandlerFactory)
 * para registrar os handlers globais do PHP.
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 11:00:00 (criação)
 */
final class ErrorHandlerSetup
{
    private ErrorHandlerFactory $handlerFactory;
    private LogService $logService;

    /**
     * Construtor do ErrorHandlerSetup.
     *
     * @param ConfigService $configService O serviço de configuração.
     */
    public function __construct(ConfigService $configService)
    {
        // Instancia os serviços necessários, injetando as dependências
        $metaLogService = new MetaLogService($configService);
        $this->logService = new LogService($configService, $metaLogService);
        $this->handlerFactory = new ErrorHandlerFactory($configService);
    }

    /**
     * Registra os tratadores globais de erros e exceções.
     */
    public function register(): void
    {
        $visualHandler = $this->handlerFactory->create();

        set_exception_handler([$this, 'handleException']);

        set_error_handler(
            function (int $errno, string $errstr, string $errfile, int $errline) use ($visualHandler): bool {
                // Converte erros do PHP em exceções para um tratamento unificado
                $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);
                $this->handleException($exception);
                return true;
            }
        );
    }

    /**
     * Trata uma exceção, registrando-a no log e exibindo-a.
     *
     * @param Throwable $exception A exceção ou erro capturado.
     */
    public function handleException(Throwable $exception): void
    {
        try {
            // 1. Loga a exceção com contexto completo usando o LogService.
            $this->logService->getLogger()->critical(
                $exception->getMessage(),
                [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            // 2. Encaminha a exceção para o tratador visual apropriado.
            $visualHandler = $this->handlerFactory->create();
            $visualHandler->handle($exception);

        } catch (Throwable $e) {
            // Fallback de segurança: se o próprio sistema de log falhar,
            // registra o erro original e o erro do log no error_log do PHP.
            error_log("ERRO ORIGINAL: " . $exception->getMessage() . " em " . $exception->getFile() . ":" . $exception->getLine());
            error_log("ERRO NO SISTEMA DE LOG: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
            // Exibe uma mensagem mínima para o usuário
            echo "Ocorreu um erro crítico no sistema. Por favor, contate o suporte.";
        }
    }
}
