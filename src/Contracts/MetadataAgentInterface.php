<?php

declare(strict_types=1);

namespace GOlib\Log\Contracts;

/**
 * Interface para Agentes de Metadados.
 *
 * Define um contrato para classes que coletam e fornecem
 * dados de contexto adicionais para serem incluídos nos logs.
 * Isso permite que o MetaLogService seja estendido com coletores
 * de dados customizados (ex: informações do usuário, ambiente, etc.).
 *
 * @version    1.0.0
 * @author     Assistente Gemini - Madbuilder / Adianti v2.0
 * @copyright  Copyright (c) 2025-08-26
 * @date       2025-08-26 10:20:00 (criação)
 */
interface MetadataAgentInterface
{
    /**
     * Coleta e retorna um array de metadados.
     *
     * A implementação deste método deve retornar um array associativo
     * onde as chaves são os nomes dos metadados e os valores são
     * os dados coletados.
     *
     * @return array<string, mixed>
     */
    public function getMetadata(): array;
}
