<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use Throwable;
use ErrorException;
use Monolog\Handler\TestHandler;
use Monolog\Level;

/**
 * Orquestra a configuração e registro dos tratadores de erros e exceções.
 *
 * @version    3.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-27
 * @date       2025-08-26 11:00:00 (criação)
 * @date       2025-08-27 18:45:00 (alteração)
 */
final class ErrorHandlerSetup
{
    private ErrorHandlerFactory $handlerFactory;
    private LogService $logService;

    public function __construct(ConfigService $configService)
    {
        $metaLogService = new MetaLogService($configService);
        $this->logService = new LogService($configService, $metaLogService);
        $this->handlerFactory = new ErrorHandlerFactory($configService);
    }

    public function register(): void
    {
        set_exception_handler([$this, 'handleException']);

        set_error_handler(
            function (int $errno, string $errstr, string $errfile, int $errline): bool {
                $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);
                $this->handleException($exception);
                return true;
            }
        );
    }

    /**
     * Trata uma exceção, registrando-a no log e passando o ID do evento para o tratador visual.
     *
     * @param Throwable $exception A exceção ou erro capturado.
     */
    public function handleException(Throwable $exception): void
    {
        $uid = null;
        try {
            // Para capturar o ID, usamos um TestHandler temporário.
            // Ele não envia o log para lugar nenhum, apenas o armazena na memória.
            $testHandler = new TestHandler(Level::Critical);
            $this->logService->getLogger()->pushHandler($testHandler);

            // Logamos a exceção. O UidProcessor irá adicionar o 'uid' ao registro.
            $this->logService->getLogger()->critical(
                $exception->getMessage(),
                [
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ]
            );

            // Removemos o TestHandler para não interferir com os handlers reais.
            $this->logService->getLogger()->popHandler();

            // Recuperamos o registro de log que acabamos de criar.
            $records = $testHandler->getRecords();
            if (!empty($records)) {
                $uid = $records[0]->extra['uid'] ?? null;
            }

            // Agora, chamamos o tratador visual, passando o ID que capturamos.
            $visualHandler = $this->handlerFactory->create();
            $visualHandler->handle($exception, $uid);
        } catch (Throwable $e) {
            error_log("ERRO ORIGINAL: " . $exception->getMessage() . " em " . $exception->getFile() . ":" . $exception->getLine());
            error_log("ERRO NO SISTEMA DE LOG: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
            echo "Ocorreu um erro crítico no sistema. Por favor, contate o suporte.";
        }
    }
}
