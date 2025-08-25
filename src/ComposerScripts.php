<?php

declare(strict_types=1);

namespace GOlib\Log;

use Composer\Script\Event;

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
        $source = __DIR__ . '/AdiantiCoreApplication.php';
        $target = getcwd() . '/lib/adianti/core/AdiantiCoreApplication.php';

        if (!is_file($source)) {
            $event->getIO()->writeError("Source file not found: {$source}");
            return;
        }

        $targetDir = dirname($target);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                $event->getIO()->writeError("Unable to create directory: {$targetDir}");
                return;
            }
        }

        if (!copy($source, $target)) {
            $event->getIO()->writeError("Failed to copy AdiantiCoreApplication.php to {$target}");
            return;
        }

        $event->getIO()->write("AdiantiCoreApplication.php copied to lib/adianti/core");
    }
}
