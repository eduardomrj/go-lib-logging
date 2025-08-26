# Guia de Logging para Agentes de IA

## Sobre

Este arquivo fornece orientações para **OpenAI Codex**, **GitHub Copilot** e outros agentes de IA sobre como trabalhar com a biblioteca `go-lib-logging`. Descreve a estrutura do projeto, configurações e boas práticas de desenvolvimento.

## Informações Técnicas Essenciais

### Dependências e Tecnologias
- **Linguagem**: PHP (considere o composer.json como fonte da verdade da versão mínima)
- **Logging Framework**: Monolog (ver versão exata no composer.json)
- **Dependências principais**:
  - `monolog/monolog`: ^3.0
  - `filp/whoops`: ^2.15 (desenvolvimento)
- **Padrões**: PSR-4, PSR-12, PSR-3 (LoggerInterface)
- **Autoload**: Configurado via Composer com namespace `GoLibLogging\` (confirmar no composer.json)

### Fonte da Verdade
- Sempre trate o `composer.json` como referência oficial de versões, namespace e PSR-4. Evite “hardcode” de versões neste documento.

## Estrutura do projeto

```
go-lib-logging/
├── .github/
│   └── workflows/
│       └── tests.yml
├── resource/
│   ├── AdiantiCoreApplication.php
│   └── go-lib-logging.ini
├── src/
│   ├── Contracts/
│   │   └── ErrorHandlerInterface.php
│   ├── Error/
│   │   ├── ProductionErrorHandler.php
│   │   ├── TExceptionViewHandler.php
│   │   └── WhoopsErrorHandler.php
│   ├── Handler/
│   │   ├── AdiantiMailerHandler.php
│   │   └── RateLimitingDiscordHandler.php
│   └── Service/
│       ├── ConfigService.php
│       ├── ErrorHandlerFactory.php
│       ├── ErrorHandlerSetup.php
│       ├── LogService.php
│       └── MetaLogService.php
├── tests/
│   ├── Handler/
│   │   ├── AdiantiMailerHandlerTest.php
│   │   └── RateLimitingDiscordHandlerTest.php
│   └── Service/
│       ├── ConfigServiceTest.php
│       ├── LogServiceTest.php
│       └── MetaLogServiceTest.php
├── .gitignore
├── AGENTS.md
├── composer.json
├── composer.lock
├── phpunit.xml
└── README.md
```


### Referências rápidas (FQCN)
- GoLibLogging\Service\LogService
- GoLibLogging\Service\MetaLogService
- GoLibLogging\Handler\RateLimitingDiscordHandler
- GoLibLogging\Handler\AdiantiMailerHandler

### Links relativos úteis
- [src/service/LogService.php](src/service/LogService.php)
- [src/service/MetaLogService.php](src/service/MetaLogService.php)
- [src/handler/RateLimitingDiscordHandler.php](src/handler/RateLimitingDiscordHandler.php)
- [src/handler/AdiantiMailerHandler.php](src/handler/AdiantiMailerHandler.php)
- [src/logging.ini](src/logging.ini)

### Detalhamento dos Arquivos

#### Raiz do Projeto
- **`.gitignore`**: Define quais arquivos/pastas o Git deve ignorar (vendor/, logs/, etc.)
- **`composer.json`**: Configuração do Composer com namespace `GoLibLogging\`, autoload PSR-4, dependências
- **`composer.lock`**: Versões exatas das dependências instaladas (commitado no repositório)
- **`LICENSE`**: Licença MIT permitindo uso comercial e modificação
- **`README.md`**: Documentação principal para desenvolvedores humanos
- **`AGENTS.md`**: Este arquivo - guia específico para agentes de IA

#### Diretório `vendor/`
Contém todas as dependências gerenciadas pelo Composer:
- **`autoload.php`**: Arquivo principal de autoload
- **`monolog/monolog`**: Biblioteca principal de logging
- **`filp/whoops`**: Biblioteca para tratamento visual de erros em desenvolvimento
- **`psr/log`**: Interface PSR-3 para logging
- **`symfony/`**: Componentes do Symfony usados pelo Monolog

#### Diretório `src/`
**`src/service/`**:
- **`LogService.php`**: Classe singleton principal que:
  - Lê configurações do `logging.ini`
  - Configura o logger Monolog
  - Adiciona handlers baseado nas configurações
  - Implementa PSR-3 LoggerInterface
- **`MetaLogService.php`**: Registra eventos sobre o sistema de notificações (sucesso/falha de envios)

**`src/handler/`**:
- **`RateLimitingDiscordHandler.php`**: Handler customizado que:
  - Envia logs para webhook do Discord
  - Controla rate limiting (mensagens por minuto)
  - Registra tentativas no MetaLogService quando excede limite
- **`AdiantiMailerHandler.php`**: Handler para envio de emails que:
  - Integra com MailService do Adianti Framework
  - Usa configurações SMTP do banco de dados
  - Formata mensagens para email

**Arquivos de configuração e exemplos**:
- **`logging.ini`**: Arquivo principal de configuração com todas as seções e opções
- **`example_usage.php`**: Exemplos práticos de como usar a biblioteca em diferentes cenários

### Arquivos Gerados/Dinâmicos
Estes arquivos não estão no repositório mas são criados durante o uso:
- **`app/logs/`**: Diretório onde são criados os arquivos de log (configurável via logging.ini)
- **Cache do Composer**: Arquivos temporários em `vendor/composer/`

### Arquivos de Configuração
- **`composer.json`**: Define namespace `GoLibLogging\`, PSR-4 autoload, dependências
- **`src/logging.ini`**: Configuração principal dos handlers e comportamentos
- **`LICENSE`**: Licença MIT do projeto
- **`README.md`**: Documentação para desenvolvedores humanos

### Diretórios Importantes
- **`src/`**: Todo código fonte da biblioteca
- **`vendor/`**: Dependências gerenciadas pelo Composer (**nunca modificar**)
- **Raiz do projeto**: Arquivos de configuração e documentação

## Configuração Detalhada do logging.ini

### Exemplo Completo de Configuração
```ini
[logging]
environment = development
whoops = 1

[monolog_handlers]
file_handler_enabled = 1
discord_handler_enabled = 1
email_handler_enabled = 0
use_fingers_crossed = 1
notification_trigger_level = ERROR
log_warnings_enabled = 1
deduplication_time = 300

[file_handler]
path = app/logs/monolog-logging.log
days = 30

[discord_handler]
webhook_url = https://discord.com/api/webhooks/...
max_per_minute = 5

[email_handler]
to_address = admin@example.com
subject = [{app_name}] Erro no ambiente {environment}
```

### Seções de Configuração

#### [logging]
- **`environment`**: `development|production` - Afeta comportamento de handlers
- **`whoops`**: `0|1` - Integração com Whoops para debugging visual

#### [monolog_handlers]
- **`file_handler_enabled`**: `0|1` - Log rotativo em arquivo
- **`discord_handler_enabled`**: `0|1` - Notificações Discord
- **`email_handler_enabled`**: `0|1` - Notificações por email
- **`use_fingers_crossed`**: `0|1` - Buffer logs até trigger level
- **`notification_trigger_level`**: Níveis PSR-3 válidos
- **`log_warnings_enabled`**: `0|1` - Incluir WARNING/NOTICE em notificações
- **`deduplication_time`**: Segundos para agrupar mensagens idênticas

#### [file_handler]
- **`path`**: Caminho relativo ou absoluto para arquivo de log
- **`days`**: Dias de retenção antes da rotação

#### [discord_handler]
- **`webhook_url`**: URL completa do webhook Discord
- **`max_per_minute`**: Limite de mensagens/minuto (evita rate limiting)

#### [email_handler]
- **`to_address`**: Email destinatário
- **`subject`**: Assunto com placeholders: `{app_name}`, `{environment}`

### Glossário rápido de níveis (PSR-3)
- DEBUG < INFO < NOTICE < WARNING < ERROR < CRITICAL < ALERT < EMERGENCY

## Padrões de Código para IA

### Instanciação do Logger
```php
use GoLibLogging\Service\LogService;

// CORRETO: Sempre usar getInstance()
$logger = LogService::getInstance();

// INCORRETO: Não instanciar diretamente
// $logger = new LogService(); // ❌
```

### Registro de Logs com Contexto
```php
// Estrutura recomendada para contexto
$context = [
    'user_id' => $userId,
    'file' => __FILE__,
    'line' => __LINE__,
    'method' => __METHOD__,
    'additional_data' => $data
];

$logger->error('Mensagem do erro', $context);
$logger->warning('Aviso importante', $context);
$logger->info('Informação relevante', $context);
```

### Tratamento de Exceções
```php
try {
    // código que pode gerar exceção
} catch (Exception $e) {
    $logger->error('Erro capturado: ' . $e->getMessage(), [
        'exception' => $e,
        'trace' => $e->getTraceAsString(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

## Diretrizes para IA

### ✅ Boas Práticas
1. **Sempre verificar se o LogService está configurado** antes de sugerir uso
2. **Incluir contexto relevante** em todos os logs gerados
3. **Usar níveis apropriados**: ERROR para erros, WARNING para avisos, INFO para informações
4. **Seguir PSR-3** para compatibilidade com outras bibliotecas
5. **Não hardcoded configurações** - sempre usar logging.ini

### ❌ Evitar
1. **Não instanciar LogService diretamente** - sempre usar singleton
2. **Não modificar vendor/** - alterações devem ser no src/
3. **Não criar handlers customizados** sem seguir padrão existente
4. **Não ignorar rate limiting** em handlers de notificação
5. **Não fazer logs excessivos** em loops ou operações frequentes

### Checklist rápido para IA
- Validar versões e namespace no composer.json
- Não inserir segredos reais (webhooks, SMTP) em commits
- Usar sempre LogService::getInstance()
- Incluir contexto mínimo: file, line, user_id (se houver)
- Conferir `use_fingers_crossed` e `notification_trigger_level` antes de sugerir handlers de notificação
- Respeitar `deduplication_time` e `max_per_minute`

### Cenários Comuns de Uso
1. **Debugging**: Use DEBUG/INFO com contexto detalhado
2. **Monitoramento**: Use WARNING para situações anômalas
3. **Alertas críticos**: Use ERROR/CRITICAL para falhas que requerem atenção
4. **Integração com Adianti**: Prefira AdiantiMailerHandler para emails

## Segurança e Gestão de Segredos
- Nunca comitar `discord_handler.webhook_url` real; use placeholders e variáveis de ambiente quando possível.
- Para SMTP, mantenha credenciais fora do VCS (ex.: vault, env, store seguro).
- Revogue webhooks expostos e rotacione segredos comprometidos.
- Se precisar registrar configs em logs, masque segredos (ex.: `xxxx`).

## Troubleshooting para IA

### Problemas Comuns
1. **Logger não inicializa**: Verificar se logging.ini existe e é válido
2. **Notificações não chegam**: Verificar configurações de webhook/email
3. **Rate limiting**: Ajustar max_per_minute ou implementar buffer
4. **Logs duplicados**: Configurar deduplication_time adequadamente

### Validação de Configuração
```php
// Exemplo para validar se logging está funcionando
if (!LogService::getInstance()) {
    throw new Exception('LogService não pôde ser inicializado');
}
```

### Limitações conhecidas
- Rate limit do Discord calculado por minuto; bursts acima do limite serão ignorados e registrados no meta-log.
- Deduplicação agrupa mensagens idênticas durante `deduplication_time` e pode adiar notificações.
- `whoops` deve permanecer ativo apenas em desenvolvimento.
- Caminhos relativos do `file_handler.path` dependem do diretório de execução do processo PHP.

## Integração com Frameworks

### Adianti Framework
- Use `AdiantiMailerHandler` para aproveitar configurações SMTP existentes
- Configure path relativo a partir da raiz do projeto Adianti
- Considere usar TTransaction para logs de banco de dados

### Frameworks Genéricos
- LogService implementa PSR-3, compatível com qualquer framework PSR
- Use dependency injection quando possível
- Configure path absoluto ou relativo conforme estrutura do projeto

## Exemplos de Integração

Ver `src/example_usage.php` para exemplos práticos de:
- Configuração básica
- Logging com contexto
- Tratamento de exceções
- Integração com diferentes ambientes

### Comandos rápidos (execução local)
```bash
composer install
php -v
php src/example_usage.php
# Atualizar a estrutura de pastas deste arquivo
php scripts/update-project-structure.php
```

---

*Este guia é otimizado para assistir IA LLMs na geração de código consistente e eficiente com go-lib-logging.*
