<?php

declare(strict_types=1);

namespace GOlib\Log\Cache;

use DateInterval;
use FilesystemIterator;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use GOlib\Log\Contracts\CacheInterface;

/**
 * Implementação de cache baseada em sistema de arquivos.
 *
 * Armazena cada item de cache como um arquivo serializado em um diretório específico.
 * Inclui suporte para TTL (Time To Live) e um mecanismo de limpeza (garbage collection).
 *
 * @version    1.0.1
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 10:25:00 (criação)
 * @date       2025-08-26 10:20:00 (alteração)
 */
class FileCache implements CacheInterface
{
    private string $cacheDir;
    private int $gcProbability;

    /**
     * Construtor da classe FileCache.
     *
     * @param string|null $cacheDir Diretório para armazenar os arquivos de cache.
     * Se nulo, usará um subdiretório no diretório temporário do sistema.
     * @param int $gcProbability A probabilidade (em porcentagem) de que o processo de
     * garbage collection (limpeza) seja executado. Padrão é 1 (1%).
     */
    public function __construct(?string $cacheDir = null, int $gcProbability = 1)
    {
        $this->cacheDir = $cacheDir ?? rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'golib-cache';
        $this->gcProbability = $gcProbability;

        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0775, true);
        }

        $this->collectGarbage();
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        $path = $this->getFilePath($key);
        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $data = unserialize($content);
        return $data['expires_at'] === null || $data['expires_at'] > time();
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return $default;
        }

        $path = $this->getFilePath($key);
        $content = file_get_contents($path);
        $data = unserialize($content);

        return $data['value'];
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value, int|DateInterval|null $ttl = null): bool
    {
        $path = $this->getFilePath($key);
        $expiresAt = $this->calculateExpiresAt($ttl);

        $data = serialize([
            'value' => $value,
            'expires_at' => $expiresAt,
        ]);

        return file_put_contents($path, $data, LOCK_EX) !== false;
    }

    /**
     * Gera o caminho completo para o arquivo de cache.
     *
     * @param string $key A chave do cache.
     * @return string O caminho absoluto do arquivo.
     */
    private function getFilePath(string $key): string
    {
        // Usa hash para evitar problemas com caracteres inválidos em nomes de arquivo.
        return $this->cacheDir . DIRECTORY_SEPARATOR . sha1($key) . '.cache';
    }

    /**
     * Calcula o timestamp de expiração com base no TTL.
     *
     * @param int|DateInterval|null $ttl
     * @return int|null
     */
    private function calculateExpiresAt(int|DateInterval|null $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new \DateTimeImmutable())->add($ttl)->getTimestamp();
        }

        return time() + $ttl;
    }

    /**
     * Executa o processo de limpeza de arquivos de cache expirados.
     */
    private function collectGarbage(): void
    {
        if (random_int(1, 100) > $this->gcProbability) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->cacheDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'cache') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content) {
                $data = unserialize($content);
                if ($data['expires_at'] !== null && $data['expires_at'] <= time()) {
                    @unlink($file->getPathname());
                }
            }
        }
    }
}
