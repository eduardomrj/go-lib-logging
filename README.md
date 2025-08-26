# **eduardomrj/go-lib-logging**

Biblioteca avançada de logging e captura de erros para PHP 8.2+, otimizada para o ecossistema MadBuilder/Adianti 7.5. Utiliza **Monolog** para um sistema de log robusto e **Whoops** para páginas de erro amigáveis em desenvolvimento.

## **Arquitetura (v2.0+)**

A versão 2.0 da biblioteca foi completamente refatorada para adotar os princípios de **Injeção de Dependência (DI)** e **Inversão de Controle (IoC)**. Isso elimina o uso de métodos estáticos e singletons, resultando em um código mais desacoplado, testável e aderente aos padrões SOLID.

## **Requisitos**

* PHP 8.2+  
* monolog/monolog ^3.9.0  
* filp/whoops ^2.18.4

## **Instalação no MadBuilder**

1. **Adicionar Repositório**: Em Composer → Minhas configurações, adicione:  
   repositories.go-lib-logging vcs https://github.com/eduardomrj/go-lib-logging.git

2. **Adicionar Pacote**: Em Composer → Meus Pacotes, adicione a versão desejada:  
   eduardomrj/go-lib-logging:^2.0

## **Configuração**

1. **Copie o arquivo de configuração**:  
   * De: vendor/eduardomrj/go-lib-logging/resource/go-lib-logging.ini  
   * Para: app/config/go-lib-logging.ini no seu projeto.  
2. **Personalize o .ini**: Ajuste as configurações conforme os cenários descritos no [Manual de Utilização](https://www.google.com/search?q=resource/MANUAL.md). Garanta que o diretório de log (files/logs/) tenha permissão de escrita.  
3. **Substitua a AdiantiCoreApplication**:  
   * Faça um backup do arquivo lib/adianti/core/AdiantiCoreApplication.php do seu projeto.  
   * Substitua todo o conteúdo dele pelo código fornecido em:  
     vendor/eduardomrj/go-lib-logging/resource/AdiantiCoreApplication.php.

## **Como Usar (v2.0+)**

A nova versão centraliza toda a inicialização. Após seguir os passos de configuração, a biblioteca é **automaticamente ativada** pelo AdiantiCoreApplication. Você não precisa instanciar os serviços manualmente.

Para registrar logs em suas classes, use o serviço de log do Adianti, que agora será potencializado pela nossa biblioteca.

### **Exemplo de Log em uma Classe de Controle**

\<?php

use Adianti\\Control\\TPage;  
use Adianti\\Log\\TLogger;

class MinhaClasseControle extends TPage  
{  
    public function \_\_construct()  
    {  
        parent::\_\_construct();

        try {  
            // Seu código aqui...  
              
            // Registrando um log de informação  
            TLogger::log('INFO', 'A classe MinhaClasseControle foi acessada.');

            // Simulando uma operação que pode falhar  
            if (empty($variavelInexistente)) {  
                throw new \\Exception('Ocorreu um erro de negócio\!');  
            }

        } catch (\\Throwable $e) {  
            // A exceção será capturada automaticamente pelo ErrorHandlerSetup  
            // e registrada conforme as regras do seu .ini.  
            // Você só precisa relançá-la para que o Adianti a exiba.  
            throw $e;  
        }  
    }  
}

Qualquer erro ou exceção não capturada em sua aplicação será automaticamente processado pelo ErrorHandlerSetup, que irá:

1. **Registrar o erro** em todos os canais configurados (arquivo, Discord, etc.).  
2. **Exibir uma tela de erro** apropriada para o ambiente (Whoops em desenvolvimento, mensagem genérica em produção).

## **Estrutura do Projeto (v2.0+)**

go-lib-logging/  
├── resource/  
│   ├── AdiantiCoreApplication.php  
│   ├── go-lib-logging.ini  
│   └── MANUAL.md  
├── src/  
│   ├── Cache/  
│   │   └── FileCache.php  
│   ├── Contracts/  
│   │   ├── CacheInterface.php  
│   │   ├── ErrorHandlerInterface.php  
│   │   └── MetadataAgentInterface.php  
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
│   \# ... (testes unitários para cada classe)  
├── .gitignore  
├── AGENTS.md  
├── composer.json  
└── README.md  
