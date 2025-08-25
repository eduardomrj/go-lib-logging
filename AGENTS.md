# Guia de Logging para Agentes de IA

## Sobre

Este arquivo fornece orientações para **OpenAI Codex** e outros agentes de IA sobre como trabalhar com a biblioteca `go-lib-logging`. Descreve a estrutura do projeto, o arquivo de configuração `logging.ini` localizado em `/src` e boas práticas de uso.

## Estrutura do Projeto

O código principal está localizado em `src/` e organizado da seguinte forma:

### Serviços
- **`src/service/LogService.php`**: Classe singleton que configura o logger e adiciona handlers com base nas configurações do `logging.ini`. Habilita log em arquivo, notificações para Discord ou e-mail e aplica deduplication e fingers-crossed conforme definido nas configurações.
- **`src/service/MetaLogService.php`**: Responsável por registrar no arquivo de log eventos sobre o envio de notificações, como sucesso ou falha.

### Handlers
- **`src/handler/RateLimitingDiscordHandler.php`**: Handler que envia logs para um webhook do Discord. Verifica se o limite de mensagens por minuto foi excedido antes de enviar; caso contrário, registra a tentativa no meta-log.
- **`src/handler/NativeMailerHandler.php`**: Handler que monta e envia um e-mail HTML via classe TMail, usado como alternativa ao MailService.
- **`src/handler/AdiantiMailerHandler.php`**: Handler que utiliza MailService::send para enviar e-mails com log; ideal para aplicações Adianti.

## Configuração do logging.ini

O arquivo `src/logging.ini` define todas as configurações. Comentários começam com `;` e não são processados.

### Seção [logging]
- **`environment`**: Define o ambiente (`development` ou `production`) para ajustar o comportamento
- **`whoops`**: Habilita (`1`) ou desabilita (`0`) a integração com o filp/whoops, que exibe erros detalhados em desenvolvimento

### Seção [monolog_handlers]
- **`file_handler_enabled`**: Ativa o log em arquivo rotativo
- **`discord_handler_enabled`**: Ativa o envio de notificações via Discord
- **`email_handler_enabled`**: Ativa o envio de notificações via e-mail
- **`use_fingers_crossed`**: 
  - `1`: Acumula todos os logs até que ocorra um erro no nível especificado em `notification_trigger_level`, enviando um pacote único
  - `0`: Cada erro gera notificação imediatamente
- **`notification_trigger_level`**: Nível mínimo para disparar uma notificação
  - Valores válidos: `DEBUG`, `INFO`, `NOTICE`, `WARNING`, `ERROR`, `CRITICAL`, `ALERT`, `EMERGENCY`
- **`log_warnings_enabled`**: Se `1`, warnings e notices também são enviados
- **`deduplication_time`**: Intervalo (em segundos) para agrupar mensagens duplicadas e evitar spam de notificações

### Seção [file_handler]
- **`path`**: Caminho do arquivo de log rotativo (ex: `app/logs/monolog-logging.log`)
- **`days`**: Quantidade de dias que os logs devem permanecer antes da rotação

### Seção [discord_handler]
- **`webhook_url`**: URL do webhook do Discord para onde as notificações serão enviadas
- **`max_per_minute`**: Limite de mensagens por minuto para evitar bloqueio por rate limit. Se excedido, o handler registra a situação no meta-log

### Seção [email_handler]
- **`to_address`**: Destinatário das notificações por e-mail
- **`subject`**: Assunto do e-mail. Pode incluir placeholders como `{app_name}` e `{environment}` para substituição dinâmica

## Recomendações de Uso

### Configuração por Ambiente
- **Desenvolvimento**: 
  - Mantenha `whoops = 1` e `log_warnings_enabled = 1` para visualizar avisos detalhados
- **Produção**: 
  - Defina `whoops = 0` e ajuste `notification_trigger_level` para `ERROR` ou superior

### Evitar Spam
- Utilize `deduplication_time` para agrupar mensagens idênticas em um intervalo. Particularmente útil quando warnings são frequentes
- Configure `max_per_minute` no Discord para limitar quantas notificações podem ser enviadas por minuto

### Handlers de Email
- **AdiantiMailerHandler**: Usa o MailService do Adianti para ler configurações de SMTP do banco de dados e enviar e-mails
- **NativeMailerHandler**: Se preferir usar via TMail, ajuste o código para usá-lo e preencha as chaves de SMTP no config

### Inserção de Contexto
Ao registrar logs, inclua variáveis de contexto como arquivo, linha, e ID do usuário. Os handlers já formatam e apresentam essas informações nas mensagens de Discord e e-mail.

## Padrões de Estilo e Desenvolvimento

- Este projeto segue **PSR-4/PSR-12** (padrões PHP)
- Mantenha nomes de classes em StudlyCase
- Use autoload definido em `composer.json`
- **Não modifique arquivos dentro de `vendor/`**
- Ajustes de configuração devem ser feitos **apenas no `logging.ini`**
- Realize commit das alterações no `logging.ini`
- Documente qualquer valor não-padrão neste `AGENTS.md` para que outros desenvolvedores e a Codex saibam como prosseguir

## Referências

- [LogService.php](https://github.com/eduardomrj/go-lib-logging/blob/b845175ea94f3885b50f8f5c2ba2d8041c47475e/src/service/LogService.php)
- [RateLimitingDiscordHandler.php](https://github.com/eduardomrj/go-lib-logging/blob/b845175ea94f3885b50f8f5c2ba2d8041c47475e/src/handler/RateLimitingDiscordHandler.php)
- [NativeMailerHandler.php](https://github.com/eduardomrj/go-lib-logging/blob/b845175ea94f3885b50f8f5c2ba2d8041c47475e/src/handler/NativeMailerHandler.php)
- [AdiantiMailerHandler.php](https://github.com/eduardomrj/go-lib-logging/blob/b845175ea94f3885b50f8f5c2ba2d8041c47475e/src/handler/AdiantiMailerHandler.php)

---
*Que a automação esteja com você!*
