# eduardomrj/go-lib-logging

Biblioteca para logging e captura de erros utilizando:

- **monolog/monolog** para centralizar e formatar logs
- **filp/whoops** para páginas de erro amigáveis

Compatível com **PHP 8.2** e **Adianti 7.5**.

## Estrutura

```
go-lib-logging/
├── src/
│   ├── contracts/
│   │   └── ErrorHandlerInterface.php
│   ├── error/
│   │   ├── ProductionErrorHandler.php
│   │   ├── TExceptionViewHandler.php
│   │   └── WhoopsErrorHandler.php
│   ├── handler/
│   │   ├── AdiantiMailerHandler.php
│   │   ├── NativeMailerHandler.php
│   │   └── RateLimitingDiscordHandler.php
│   ├── service/
│   │   ├── ConfigService.php
│   │   ├── ErrorHandlerFactory.php
│   │   ├── LogService.php
│   │   └── MetaLogService.php
│   └── logging.ini
├── AGENTS.md
├── composer.json
└── README.md
```

## Requisitos

- PHP 8.2+
- monolog/monolog 3.x
- filp/whoops 2.x

## Instalação via Composer

```sh
composer config repositories.go-lib-logging vcs https://github.com/eduardomrj/go-lib-logging.git
composer require eduardomrj/go-lib-logging:^1.0
```

Caso ainda não exista uma tag publicada, use o alias de branch:

```sh
composer require eduardomrj/go-lib-logging:1.0.x-dev@dev
```

## Configuração

1. Copie `src/logging.ini` para o diretório de configuração do seu projeto.
2. Ajuste as opções conforme necessário. Exemplo:

```ini
[logging]
environment = "development"
whoops = 1

[monolog_handlers]
file_handler_enabled = 1
discord_handler_enabled = 0
email_handler_enabled = 0
use_fingers_crossed = 0
notification_trigger_level = "WARNING"
log_warnings_enabled = 1
```

## Exemplo de Uso

```php
use App\Lib\GOlib\Log\Service\LogService;

$logger = LogService::getInstance()->getLogger();
$logger->info('Iniciando aplicação');

try {
    // código que pode lançar exceção
} catch (Throwable $e) {
    LogService::logAndThrow($e);
}
```

## Versionamento

Tags SemVer `vMAJOR.MINOR.PATCH` (ex.: `v1.0.0`). Ao criar uma tag, o workflow Release publica a release automaticamente.
