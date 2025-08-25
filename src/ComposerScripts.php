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

        $source = realpath(__DIR__ . '/../resources/AdiantiCoreApplication.php');
        $target = $projectRoot . '/lib/adianti/core/AdiantiCoreApplication.php';

        if (!$source) {
            $io->writeError("<error>Source file not found: " . __DIR__ . "/../resources/AdiantiCoreApplication.php</error>");
            return;
        }

        $targetDir = dirname($target);
        if (!$filesystem->ensureDirectoryExists($targetDir)) {
            $io->writeError("<error>Unable to create directory: {$targetDir}</error>");
            return;
        }

        if (copy($source, $target)) {
            $io->write("<info>GO-Lib Logging: 'AdiantiCoreApplication.php' copied successfully to 'lib/adianti/core/'</info>");
        } else {
            $io->writeError("<error>GO-Lib Logging: Failed to copy 'AdiantiCoreApplication.php' to {$target}</error>");
        }
    }
}