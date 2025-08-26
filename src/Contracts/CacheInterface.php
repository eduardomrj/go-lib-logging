<?php

declare(strict_types=1);

namespace GOlib\Log\Contracts;

/**
 * Interface para implementações de cache.
 *
 * Define um contrato para operações básicas de get, set e has,
 * permitindo que diferentes mecanismos de cache (arquivo, Redis, etc.)
 * sejam usados de forma intercambiável.
 *
 * @version    1.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 10:18:00
 */
interface CacheInterface
{
    /**
     * Verifica se uma chave existe no cache.
     *
     * @param string $key A chave única do item de cache.
     * @return bool Retorna true se a chave existir, false caso contrário.
     */
    public function has(string $key): bool;

    /**
     * Recupera um item do cache.
     *
     * @param string $key A chave do item a ser recuperado.
     * @param mixed $default O valor padrão a ser retornado se a chave não for encontrada.
     * @return mixed O valor do item de cache ou o valor padrão.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Armazena um item no cache por um determinado tempo.
     *
     * @param string $key A chave sob a qual o item será armazenado.
     * @param mixed $value O valor a ser armazenado.
     * @param int|\DateInterval|null $ttl O tempo de vida (Time To Live) do item em segundos.
     * Se for nulo, o item será armazenado indefinidamente.
     * @return bool Retorna true em caso de sucesso, false em caso de falha.
     */
    public function set(string $key, mixed $value, int|\DateInterval|null $ttl = null): bool;
}
