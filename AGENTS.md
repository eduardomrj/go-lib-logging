# **Guia de Logging para Agentes de IA (v2.0+)**

## **Sobre**

Este arquivo fornece orientações para **OpenAI Codex**, **GitHub Copilot**, **Gemini** e outros agentes de IA sobre como trabalhar com a biblioteca go-lib-logging v2.0+. Descreve a nova arquitetura, configurações e boas práticas de desenvolvimento.

## **Arquitetura v2.0+: Injeção de Dependência**

A versão 2.0 abandonou o padrão Singleton e métodos estáticos. A biblioteca agora opera com base em **Injeção de Dependência (DI)**. A inicialização é centralizada na classe GOlib\\Log\\Service\\ErrorHandlerSetup, que constrói e injeta todas as dependências necessárias (ConfigService, LogService, MetaLogService, etc.).

**Diretriz principal para a IA**: Não instanciar serviços diretamente nem usar métodos estáticos que não existem mais. A interação com a biblioteca é feita através da configuração no .ini e da captura de \\Throwable.

### **Fonte da Verdade**

* Sempre trate o composer.json como referência oficial de versões e namespaces.  
* O ponto de entrada da aplicação é lib/adianti/core/AdiantiCoreApplication.php, que agora instancia e registra o ErrorHandlerSetup.

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
│   │   \# ... (Production, TExceptionView, Whoops)  
│   ├── Handler/  
│   │   \# ... (AdiantiMailer, RateLimitingDiscord)  
│   └── Service/  
│       ├── ConfigService.php  
│       ├── ErrorHandlerFactory.php  
│       ├── ErrorHandlerSetup.php  
│       ├── LogService.php  
│       └── MetaLogService.php  
├── tests/  
\# ... (testes unitários)

## **Padrões de Código para IA (v2.0+)**

### **NÃO FAZER: Padrão Antigo (Obsoleto)**

// INCORRETO: O método getInstance() não existe mais.  
// $logger \= LogService::getInstance()-\>getLogger(); // ❌  
// $logger-\>info('Isso vai causar um erro fatal.'); // ❌

### **FAZER: Padrão Correto (Atual)**

A biblioteca é inicializada automaticamente. Para registrar logs dentro do Adianti Framework, use a classe TLogger padrão, que será interceptada e gerenciada pela nossa biblioteca.

use Adianti\\Log\\TLogger;  
use Adianti\\Control\\TPage;

class ExemploControle extends TPage  
{  
    public function algumMetodo()  
    {  
        // CORRETO: Usar o TLogger do Adianti.  
        TLogger::log('INFO', 'Esta é a forma correta de registrar um log.');

        try {  
            // ... código que pode falhar  
        } catch (\\Throwable $e) {  
            // A exceção será capturada e logada automaticamente  
            // pelo ErrorHandlerSetup. Apenas relance para o Adianti.  
            throw $e;  
        }  
    }  
}

### **Diretrizes para IA**

1. **Não instanciar serviços**: A IA não deve gerar código como new LogService(...) ou new ConfigService(...). Toda a inicialização é feita no AdiantiCoreApplication.php.  
2. **Usar TLogger**: Para registrar logs, a IA deve sempre usar a fachada Adianti\\Log\\TLogger.  
3. **Capturar \\Throwable**: Blocos try-catch devem capturar \\Throwable para garantir que Error e Exception sejam tratados.  
4. **Não hardcodificar configurações**: As configurações (níveis de log, webhooks) devem ser gerenciadas exclusivamente pelo arquivo app/config/go-lib-logging.ini.  
5. **Verificar o README.md**: O README.md é a documentação principal para desenvolvedores humanos e contém o fluxo de configuração essencial.

*Este guia é otimizado para assistir IA LLMs na geração de código consistente e eficiente com go-lib-logging v2.0+.*