<?php

declare(strict_types=1);

namespace GOlib\Log\Service;

use RuntimeException;

/**
 * Serviço para Acesso Centralizado às Configurações.
 *
 * Carrega e fornece acesso a chaves ou seções inteiras de arquivos .ini.
 * Esta versão foi refatorada para ser instanciável e suportar injeção
 * de dependência, eliminando o acesso estático.
 *
 * @version    2.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-12 11:45:00 (criação)
 * @date       2025-08-26 10:28:00 (alteração)
 */
class ConfigService
{
    private array $config = [];

    /**
     * Construtor do serviço de configuração.
     *
     * @param string $logIniPath Caminho para o arquivo go-lib-logging.ini.
     * @param string|null $appIniPath Caminho opcional para o application.ini.
     * @throws RuntimeException Se o arquivo de log .ini não for encontrado ou não puder ser lido.
     */
    public function __construct(string $logIniPath, ?string $appIniPath = null)
    {
        $this->loadConfig($logIniPath, $appIniPath);
    }

    /**
     * Carrega e mescla as configurações dos arquivos .ini.
     *
     * @param string $logIniPath
     * @param string|null $appIniPath
     */
    private function loadConfig(string $logIniPath, ?string $appIniPath): void
    {
        $appConfig = [];
        if ($appIniPath && is_readable($appIniPath)) {
            $appConfig = parse_ini_file($appIniPath, true) ?: [];
        }

        if (!is_readable($logIniPath)) {
            error_log('[go-lib-logging] Arquivo de configuração ausente ou sem permissão de leitura: ' . $logIniPath);
            throw new RuntimeException('Arquivo de configuração de log não encontrado: ' . $logIniPath);
        }

        $logConfig = parse_ini_file($logIniPath, true);
        if ($logConfig === false) {
            error_log('[go-lib-logging] Falha ao ler configuração de log: ' . $logIniPath);
            throw new RuntimeException('Falha ao ler configuração de log: ' . $logIniPath);
        }

        // Mescla as configurações, com as de log tendo precedência.
        $this->config = array_merge_recursive($appConfig, $logConfig);
    }

    /**
     * Busca uma configuração.
     *
     * @param string $section A seção do .ini (ex: 'logging', 'discord_handler').
     * @param string|null $key A chave específica a ser buscada. Se nulo, retorna a seção inteira.
     * @param mixed $default O valor padrão a ser retornado se a chave ou seção não for encontrada.
     * @return mixed O valor da configuração.
     */
    public function get(string $section, ?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config[$section] ?? $default;
        }
        
        return $this->config[$section][$key] ?? $default;
    }

    /**
     * Valida se as configurações essenciais estão presentes.
     *
     * @param array $requiredKeys Um array associativo onde a chave é a seção e o valor é um array de chaves obrigatórias.
     * Ex: ['discord_handler' => ['webhook_url'], 'file_handler' => ['path']]
     * @throws RuntimeException Se uma chave obrigatória estiver ausente.
     */
    public function validate(array $requiredKeys): void
    {
        foreach ($requiredKeys as $section => $keys) {
            if (!isset($this->config[$section])) {
                throw new RuntimeException("A seção de configuração obrigatória '{$section}' está ausente.");
            }

            foreach ($keys as $key) {
                if (!isset($this->config[$section][$key])) {
                    throw new RuntimeException("A chave de configuração obrigatória '{$key}' na seção '{$section}' está ausente.");
                }
            }
        }
    }
}
