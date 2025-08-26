Manual de Utilização e Cenários - go-lib-loggingEste manual apresenta cenários de configuração comuns para a biblioteca go-lib-logging, ajudando você a adaptar o sistema de logs às necessidades do seu ambiente.Cenário 1: Ambiente de Desenvolvimento AgressivoObjetivo: Ver todos os erros possíveis na tela, registrar absolutamente tudo em arquivo e não receber nenhuma notificação para não gerar ruído.Use este cenário para: Desenvolver novas funcionalidades e depurar problemas localmente.Configuração (go-lib-logging.ini):[logging]
environment = "development"
whoops = 1

[notification_strategy]
use_fingers_crossed = 0
log_warnings_enabled = 0 ; Desliga globalmente as notificações de warnings

[file_handler]
enabled = 1
level = "DEBUG" ; Captura tudo, desde debug a erros críticos

[discord_handler]
enabled = 0 ; Desliga o handler do Discord

[email_handler]
enabled = 0 ; Desliga o handler de Email
Por que funciona?environment = "development" e whoops = 1 ativam a tela de erro detalhada do Whoops.file_handler.level = "DEBUG" garante que qualquer log, de qualquer nível, seja salvo no arquivo monolog-logging.log.discord_handler.enabled = 0 e email_handler.enabled = 0 simplesmente desativam o envio de qualquer notificação.Cenário 2: Ambiente de Produção Padrão (Recomendado)Objetivo: Registrar tudo em arquivo para auditoria, notificar apenas erros graves (ERROR ou superior) no Discord e mostrar uma mensagem de erro genérica para o usuário final.Use este cenário para: Aplicações em produção onde a estabilidade é crucial e você só precisa ser alertado quando algo realmente quebra.Configuração (go-lib-logging.ini):[logging]
environment = "production"

[notification_strategy]
use_fingers_crossed = 0
log_warnings_enabled = 0 ; Ignora warnings para notificações

[file_handler]
enabled = 1
level = "DEBUG" ; Continua registrando tudo no arquivo para análise posterior

[discord_handler]
enabled = 1
level = "ERROR" ; Só envia notificação se for ERROR ou mais grave

[email_handler]
enabled = 0
Por que funciona?environment = "production" esconde os detalhes técnicos do erro do usuário.log_warnings_enabled = 0 garante que o sistema de notificação ignore completamente os WARNINGs.discord_handler.level = "ERROR" atua como o filtro final, garantindo que apenas erros que passaram pela estratégia (neste caso, ERROR, CRITICAL, etc.) sejam de fato enviados.Cenário 3: Monitoramento Proativo em ProduçãoObjetivo: Além de ser notificado sobre erros graves, você quer ser alertado sobre WARNINGs para corrigir problemas potenciais antes que se tornem críticos. Para evitar spam, os warnings repetidos serão agrupados.Use este cenário para: Aplicações maduras em produção onde você quer manter a qualidade do código e identificar falhas lógicas silenciosas.Configuração (go-lib-logging.ini):[logging]
environment = "production"

[notification_strategy]
use_fingers_crossed = 0
log_warnings_enabled = 1 ; ATIVA a estratégia de notificação para warnings
deduplication_time = 300 ; Agrupa warnings idênticos por 5 minutos

[file_handler]
enabled = 1
level = "DEBUG"

[discord_handler]
enabled = 1
level = "WARNING" ; DIMINUI o nível para permitir a passagem de warnings

[email_handler]
enabled = 1
level = "CRITICAL" ; E-mail continua reservado para desastres
Por que funciona?log_warnings_enabled = 1 "abre a porta" para os warnings e ativa o DeduplicationHandler para evitar spam.discord_handler.level = "WARNING" "autoriza a entrada" dos warnings que foram liberados pela estratégia. A combinação dessas duas flags é essencial.email_handler.level = "CRITICAL" garante que seu e-mail não seja inundado, recebendo apenas os erros mais catastróficos.Cenário 4: Depuração de Erros Complexos em ProduçãoObjetivo: Um erro raro e difícil de reproduzir está acontecendo em produção. Você quer receber uma notificação com o erro e todo o histórico de logs (INFO, DEBUG) que aconteceram imediatamente antes dele.Use este cenário para: Investigar problemas intermitentes sem ter que encher seus logs ou canais de notificação com informações de debug o tempo todo.Configuração (go-lib-logging.ini):[logging]
environment = "production"

[notification_strategy]
use_fingers_crossed = 1 ; ATIVA a estratégia de acumular e disparar
trigger_level = "ERROR" ; O gatilho para enviar o pacote de logs é um ERROR

[file_handler]
enabled = 1
level = "DEBUG"

[discord_handler]
enabled = 1
level = "DEBUG" ; IMPORTANTE: O level deve ser baixo para aceitar o pacote completo

[email_handler]
enabled = 0
Por que funciona?use_fingers_crossed = 1 instrui a biblioteca a guardar todos os logs na memória.trigger_level = "ERROR" define que o "disparo" ocorrerá quando um log de nível ERROR for registrado.Quando o disparo ocorre, um pacote contendo o ERROR + todos os INFO e DEBUG anteriores é enviado.discord_handler.level = "DEBUG" é crucial para garantir que o handler do Discord aceite esse pacote completo, que contém logs de todos os níveis. Se o nível fosse ERROR, os logs de INFO e DEBUG do pacote seriam filtrados e você perderia o contexto.