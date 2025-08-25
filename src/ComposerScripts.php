<?php

declare(strict_types=1);

namespace GOlib\Log;

use Composer\Script\Event;
use Composer\Util\Filesystem;

/**
 * Composer scripts for library installation lifecycle.
 */
class ComposerScripts
{
    /**
     * Handle post-install Composer event.
     */
    public static function postInstall(Event $event): void
    {
        self::copyAdiantiCoreApplication($event);
    }

    /**
     * Handle post-update Composer event.
     */
    public static function postUpdate(Event $event): void
    {
        self::copyAdiantiCoreApplication($event);
    }

    /**
     * Copy AdiantiCoreApplication.php to project's Adianti core directory.
     */
    private static function copyAdiantiCoreApplication(Event $event): void
    {
        $io = $event->getIO();
        $filesystem = new Filesystem();
        
        // Caminho do diretório 'vendor'
        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        // O diretório raiz do projeto é o pai do diretório 'vendor'
        $projectRoot = dirname($vendorDir);

        // Múltiplos caminhos possíveis para o arquivo fonte
        $possibleSources = [
            __DIR__ . '/../resources/AdiantiCoreApplication.php',
            __DIR__ . '/resources/AdiantiCoreApplication.php',
            __DIR__ . '/../AdiantiCoreApplication.php'
        ];

        $source = null;
        foreach ($possibleSources as $path) {
            if (file_exists($path)) {
                $source = realpath($path);
                break;
            }
        }

        if (!$source) {
            $io->writeError("<error>GO-Lib Logging: Source file 'AdiantiCoreApplication.php' not found in any of these locations:</error>");
            foreach ($possibleSources as $path) {
                $io->writeError("  - " . $path);
            }
            return;
        }

        $target = $projectRoot . '/lib/adianti/core/AdiantiCoreApplication.php';
        $targetDir = dirname($target);

        $io->write("<info>GO-Lib Logging: Attempting to copy from '{$source}' to '{$target}'</info>");

        // Criar diretório de destino se não existir
        if (!is_dir($targetDir)) {
            if (!$filesystem->ensureDirectoryExists($targetDir)) {
                $io->writeError("<error>GO-Lib Logging: Unable to create directory: {$targetDir}</error>");
                return;
            }
            $io->write("<info>GO-Lib Logging: Created directory: {$targetDir}</info>");
        }

        // Verificar permissões de escrita
        if (!is_writable($targetDir)) {
            $io->writeError("<error>GO-Lib Logging: No write permission for directory: {$targetDir}</error>");
            return;
        }

        // Fazer backup se o arquivo já existir
        if (file_exists($target)) {
            $backup = $target . '.backup.' . date('Y-m-d-H-i-s');
            if (copy($target, $backup)) {
                $io->write("<info>GO-Lib Logging: Existing file backed up to: {$backup}</info>");
            }
        }

        // Copiar o arquivo
        if (copy($source, $target)) {
            $io->write("<info>GO-Lib Logging: 'AdiantiCoreApplication.php' copied successfully to 'lib/adianti/core/'</info>");
        } else {
            $io->writeError("<error>GO-Lib Logging: Failed to copy 'AdiantiCoreApplication.php' to {$target}</error>");
            $io->writeError("<error>Error details: " . error_get_last()['message'] ?? 'Unknown error'</error>");
        }
    }
}