<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

/**
 * Serviço para Acesso Centralizado às Configurações
 * * @version    15.0.0
 * @author     Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-12
 * @date       2025-08-12 11:45:00
 * @description Carrega e fornece acesso a chaves ou seções inteiras dos arquivos .ini.
 */
class ConfigService
{
    private static ?array $config = null;

    private static function loadConfig(): void
    {
        if (self::$config === null) {
            $app_ini_path = 'app/config/application.ini';
            $log_ini_path = 'app/config/go-lib-logging.ini';

            $app_ini = file_exists($app_ini_path) ? parse_ini_file($app_ini_path, true) : [];
            $log_ini = file_exists($log_ini_path) ? parse_ini_file($log_ini_path, true) : [];
            
            $app_ini = is_array($app_ini) ? $app_ini : [];
            $log_ini = is_array($log_ini) ? $log_ini : [];
            
            self::$config = array_merge_recursive($app_ini, $log_ini);
        }
    }

    /**
     * Busca uma configuração.
     * Se $key for null, retorna a seção inteira.
     * Se $key for uma string, retorna a chave específica.
     *
     * @param string $section A seção do .ini (ex: 'general', 'logging').
     * @param ?string $key A chave específica a ser buscada, ou null para a seção inteira.
     * @param mixed $default O valor padrão a ser retornado se nada for encontrado.
     * @return mixed
     */
    public static function get(string $section, ?string $key = null, mixed $default = null): mixed
    {
        self::loadConfig();

        if ($key === null) {
            // Retorna a seção inteira se a chave for nula
            return self::$config[$section] ?? $default;
        }
        
        // Retorna a chave específica dentro da seção
        return self::$config[$section][$key] ?? $default;
    }
}
