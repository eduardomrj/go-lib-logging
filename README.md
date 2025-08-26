# eduardomrj/go-lib-logging

Biblioteca de logging e captura de erros para PHP (utilizada na plataforma MadBuilder), baseada em:
- monolog/monolog para centralizar e formatar logs
- filp/whoops para páginas de erro amigáveis em desenvolvimento

Compatível com PHP 8.2 e Adianti 7.5.

## Requisitos

- PHP 8.2+
- monolog/monolog ^3.9.0
- filp/whoops ^2.18.4

## Instalação

### Via Composer (projetos PHP)

1) Adicione o repositório e instale o pacote:
```sh
composer config repositories.go-lib-logging vcs https://github.com/eduardomrj/go-lib-logging.git
composer require eduardomrj/go-lib-logging:^1.0
```

Se ainda não existir uma tag publicada:
```sh
composer require eduardomrj/go-lib-logging:1.0.x-dev@dev
```

### Repositório privado (opcional)

Autentique o Composer com um Personal Access Token do GitHub (escopo repo):
```sh
composer config --global --auth github-oauth.github.com <TOKEN>
```
Substitua <TOKEN> pelo valor gerado. Alternativamente use SSH (git@github.com) com sua chave pública cadastrada.

### Instalação no MadBuilder

No MadBuilder:
1) Abra Composer → Minhas configurações e adicione:
```
repositories.go-lib-logging vcs https://github.com/eduardomrj/go-lib-logging.git
```
2) Em Composer → Meus Pacotes, adicione:
```
eduardomrj/go-lib-logging:^1.0
```
Se não houver tag:
```
eduardomrj/go-lib-logging:1.0.x-dev@dev
```

## Configuração

1) Copie o arquivo de configuração:
- De: resource/go-lib-logging.ini
- Para: app/config/go-lib-logging.ini (no seu projeto)

2) Garanta que o diretório configurado em file_handler.path exista e tenha permissão de escrita.

3) Consulte a documentação completa de opções e exemplos diretamente no arquivo:
- [resource/go-lib-logging.ini](resource/go-lib-logging.ini)

O arquivo detalha cada grupo e chave (por exemplo: [logging], [monolog_handlers], [file_handler], etc.), valores permitidos e exemplos.

## Integração com MadBuilder (substituição da AdiantiCoreApplication)

Para que o tratamento de erros e log seja inicializado automaticamente no MadBuilder, substitua a classe AdiantiCoreApplication pela versão fornecida nesta biblioteca.

1) Faça backup do arquivo original do seu projeto Adianti/MadBuilder.
2) Na plataforma MadBuilder, localize a classe AdiantiCoreApplication (ex.: lib/adianti/core/AdiantiCoreApplication.php).
3) Abra o arquivo e substitua todo o conteúdo pelo código do arquivo resource/AdiantiCoreApplication.php desta biblioteca.
4) Salve o arquivo e limpe caches do MadBuilder/Adianti, se aplicável.

Observação: a substituição é específica para uso no MadBuilder, conforme prática deste projeto.

## Exemplos de uso

### Log básico com Monolog via serviço
```php
use GOlib\Log\Service\LogService;

$logger = LogService::getInstance()->getLogger();
$logger->info('Iniciando aplicação');
$logger->warning('Algo potencialmente inesperado aconteceu', ['contexto' => 'exemplo']);
```

### Captura e registro de exceções
```php
use GOlib\Log\Service\LogService;

try {
    // código que pode lançar exceção
} catch (Throwable $e) {
    // Registra e relança, permitindo fluxo global de tratamento
    LogService::logAndThrow($e);
}
```

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

## Versionamento

Tags SemVer vMAJOR.MINOR.PATCH (ex.: v1.0.0). Ao criar uma tag, o workflow Release publica a release automaticamente.
