<?php

namespace Adianti\Core;

use Error;
use Exception;
use ReflectionClass;
use ReflectionMethod;
use Adianti\Control\TPage;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Core\AdiantiCoreTranslator;
use GOlib\Log\Service\ErrorHandlerSetup;

/**
 * Estrutura base para executar uma aplicação web.
 *
 * @version    7.5 (Adianti Framework)
 * @version    1.8.0 (GOLib-Logging Module Integration)
 * @author     Pablo Dall'Oglio (Adianti Framework) / Madbuilder / Adianti v2.0 (Integration)
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @date       2025-08-21 17:20:00 (Integration Update)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiCoreApplication
{
    private static $router;
    private static $request_id;
    private static $debug;
    
    /**
     * Ponto de entrada principal para qualquer requisição da aplicação.
     * @param bool $debug Ativa/desativa o modo de depuração.
     */
    public static function run($debug = FALSE)
    {
        self::$request_id = uniqid();
        self::$debug = $debug;

        // Registra o nosso tratador de erros global.
        ErrorHandlerSetup::register();

        $ini = AdiantiApplicationConfig::get();
        $service = isset($ini['general']['request_log_service']) ? $ini['general']['request_log_service'] : '\SystemRequestLogService';
        $class   = isset($_REQUEST['class'])    ? $_REQUEST['class']   : '';
        $static  = isset($_REQUEST['static'])   ? $_REQUEST['static']  : '';
        $method  = isset($_REQUEST['method'])   ? $_REQUEST['method']  : '';
        
        $content = '';
        
        // Mantém o tratador de erros original do Adianti como fallback.
        set_error_handler(array('AdiantiCoreApplication', 'errorHandler'));
        
        if (!empty($ini['general']['request_log']) && $ini['general']['request_log'] == '1')
        {
            if (empty($ini['general']['request_log_types']) || strpos($ini['general']['request_log_types'], 'web') !== false)
            {
                self::$request_id = $service::register( 'web');
            }
        }
        
        self::filterInput();
        
        \MadLogService::initializeDebugLogging();
        
        try
        {
            if (class_exists($class))
            {
                $rc = new ReflectionClass($class); 
                
                if (in_array(strtolower($class), array_map('strtolower', AdiantiClassMap::getInternalClasses()) ))
                {
                    ob_start();
                    new TMessage( 'error', AdiantiCoreTranslator::translate('The internal class ^1 can not be executed', " <b><i><u>{$class}</u></i></b>") );
                    $content = ob_get_contents();
                    ob_end_clean();
                }
                else if (!$rc-> isUserDefined ())
                {
                    ob_start();
                    new TMessage( 'error', AdiantiCoreTranslator::translate('The internal class ^1 can not be executed', " <b><i><u>{$class}</u></i></b>") );
                    $content = ob_get_contents();
                    ob_end_clean();
                }
                else
                {
                    if ($static)
                    {
                        $rf = new ReflectionMethod($class, $method);
                        if ($rf-> isStatic ())
                        {
                            call_user_func(array($class, $method), $_REQUEST);
                        }
                        else
                        {
                            call_user_func(array(new $class($_REQUEST), $method), $_REQUEST);
                        }
                    }
                    else
                    {
                        $page = new $class( $_REQUEST );
                        
                        ob_start();
                        $page->show( $_REQUEST );
                        $content = ob_get_contents();
                        ob_end_clean();
                    }
                }
            }
            else if (!empty($class))
            {
                new TMessage('error', AdiantiCoreTranslator::translate('Class ^1 not found', " <b><i><u>{$class}</u></i></b>") . '.<br>' . AdiantiCoreTranslator::translate('Check the class name or the file name').'.');
            }
        }
        catch (Exception | Error $e)
        {
            // CORREÇÃO: Verifica se a exceção é de permissão negada.
            // O MadBuilder/Adianti usa exceções para controlar o fluxo de permissões.
            // Se for este o caso, devemos re-lançar a exceção para que a lógica
            // do engine.php possa tratá-la e exibir a mensagem correta, em vez
            // de o nosso sistema de log a intercetar indevidamente.
            if ($e->getMessage() == _t('Permission denied'))
            {
                throw $e; // Devolve o controlo ao framework.
            }

            // Para todos os outros erros, a nossa lógica de log é executada.
            ob_end_clean();
            
            ErrorHandlerSetup::handleException($e);

            $isWarningOrNotice = false;
            if ($e instanceof \ErrorException)
            {
                $logOnlySeverities = [E_WARNING, E_NOTICE, E_USER_WARNING, E_USER_NOTICE, E_DEPRECATED, E_USER_DEPRECATED, E_STRICT];
                if (in_array($e->getSeverity(), $logOnlySeverities))
                {
                    $isWarningOrNotice = true;
                }
            }

            if (!$isWarningOrNotice)
            {
                ob_start();
                $handler = ErrorHandlerFactory::create();
                $handler->handle($e);
                $content = ob_get_contents();
                ob_end_clean();
            }
        }
        
        \MadLogService::finalizeDebugLogging();
        
        if (!$static)
        {
            echo TPage::getLoadedCSS();
        }
        echo TPage::getLoadedJS();
        
        echo $content;
    }

    /**
     * Executa um método estático ou de instância de uma classe.
     * Usado para chamadas de serviço (ex: APIs).
     * @param string $class Class Name
     * @param string $method Method Name
     * @param array $request Request Parameters
     * @param string $endpoint Endpoint name
     * @return mixed method return
     * @throws Exception if class or method not found
     */
    public static function execute($class, $method, $request, $endpoint = null)
    {
        try
        {
            self::$request_id = uniqid();
            
            $ini = AdiantiApplicationConfig::get();
            $service = isset($ini['general']['request_log_service']) ? $ini['general']['request_log_service'] : '\SystemRequestLogService'; 
            
            if (!empty($ini['general']['request_log']) && $ini['general']['request_log'] == '1')
            {
                if (empty($endpoint) || empty($ini['general']['request_log_types']) || strpos($ini['general']['request_log_types'], $endpoint) !== false)
                {
                    self::$request_id = $service::register( $endpoint );
                }
            }
            
            if (class_exists($class))
            {
                $rc = new ReflectionClass($class);
                
                if (in_array(strtolower($class), array_map('strtolower', AdiantiClassMap::getInternalClasses()) ))
                {
                    throw new Exception(AdiantiCoreTranslator::translate('The internal class ^1 can not be executed', $class ));
                }
                else if (!$rc-> isUserDefined ())
                {
                    throw new Exception(AdiantiCoreTranslator::translate('The internal class ^1 can not be executed', $class ));
                }
                
                if (method_exists($class, $method))
                {
                    $rf = new ReflectionMethod($class, $method);
                    if ($rf-> isStatic ())
                    {
                        $response = call_user_func(array($class, $method), $request);
                    }
                    else
                    {
                        $response = call_user_func(array(new $class($request), $method), $request);
                    }
                    return $response;
                }
                else
                {
                    throw new Exception(AdiantiCoreTranslator::translate('Method ^1 not found', "$class::$method"));
                }
            }
            else
            {
                throw new Exception(AdiantiCoreTranslator::translate('Class ^1 not found', $class));
            }
        }
        catch (Exception | Error $e)
        {
            // Também loga exceções que ocorrem em chamadas de serviço.
            ErrorHandlerSetup::handleException($e);
            throw $e; // Re-lança a exceção para que o chamador original saiba que algo falhou.
        }
    }
    
    /**
     * Filtra a entrada da requisição para prevenir comandos SQL não autorizados.
     */
    public static function filterInput()
    {
        if ($_REQUEST)
        {
            foreach ($_REQUEST as $key => $value)
            {
                if (is_scalar($value))
                {
                    if ( (substr(strtoupper($value),0,7) == '(SELECT') OR (substr(strtoupper($value),0,6) == 'NOESC:'))
                    {
                        $_REQUEST[$key] = '';
                        $_GET[$key]     = '';
                        $_POST[$key]    = '';
                    }
                }
                else if (is_array($value))
                {
                    foreach ($value as $sub_key => $sub_value)
                    {
                        if (is_scalar($sub_value))
                        {
                            if ( (substr(strtoupper($sub_value),0,7) == '(SELECT') OR (substr(strtoupper($sub_value),0,6) == 'NOESC:'))
                            {
                                $_REQUEST[$key][$sub_key] = '';
                                $_GET[$key][$sub_key]     = '';
                                $_POST[$key][$sub_key]    = '';
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Define um roteador customizado.
     * @param callable $callback PHP Callback
     */
    public static function setRouter(Callable $callback)
    {
        self::$router = $callback;
    }
    
    /**
     * Retorna o roteador customizado.
     * @return callable
     */
    public static function getRouter()
    {
        return self::$router;
    }
    
    /**
     * Executa um método de uma classe.
     * @param string    $class    Class Name
     * @param string   $method   Method Name
     * @param array $parameters Parameters
     */
    public static function executeMethod($class, $method = NULL, $parameters = NULL)
    {
        self::gotoPage($class, $method, $parameters);
    }
    
    /**
     * Processa a requisição e insere a saída em um template.
     * @param string $template HTML Template
     * @return string The processed template
     */
    public static function processRequest($template)
    {
        ob_start();
        AdiantiCoreApplication::run();
        $content = ob_get_contents();
        ob_end_clean();
        
        $template = str_replace('{content}', $content, $template);
        
        return $template;
    }
     
    /**
     * Redireciona para uma página.
     * @param string      $class      Class Name
     * @param string   $method     Method Name
     * @param array $parameters Parameters
     * @param callable $callback   Callback for navigation
     */
    public static function gotoPage($class, $method = NULL, $parameters = NULL, $callback = NULL)
    {
        unset($parameters['static']);
        $query = self::buildHttpQuery($class, $method, $parameters);
        
        TScript::create("__adianti_goto_page('{$query}');", true, 1);
    }
    
    /**
     * Carrega uma página via AJAX.
     * @param string      $class      Class Name
     * @param string   $method     Method Name
     * @param array $parameters Parameters
     */
    public static function loadPage($class, $method = NULL, $parameters = NULL)
    {
        $query = self::buildHttpQuery($class, $method, $parameters);
        
        TScript::create("__adianti_load_page('{$query}');", true, 1);
    }
    
    /**
     * Carrega uma página via URL.
     * @param string $query URL Query string
     */
    public static function loadPageURL($query)
    {
        TScript::create("__adianti_load_page('{$query}');", true, 1);
    }
    
    /**
     * Envia dados de um formulário via POST.
     * @param string      $formName   Form Name
     * @param string      $class      Class Name
     * @param string   $method     Method Name
     * @param array $parameters Parameters
     */
    public static function postData($formName, $class, $method = NULL, $parameters = NULL)
    {
        $url = array();
        $url['class']  = $class;
        $url['method'] = $method;
        unset($parameters['class']);
        unset($parameters['method']);
        $url = array_merge($url, (array) $parameters);
        
        TScript::create("__adianti_post_data('{$formName}', '".http_build_query($url)."');");
    }
    
    /**
     * Constrói uma query string HTTP.
     * @param string      $class      Class Name
     * @param string   $method     Method Name
     * @param array $parameters Parameters
     * @return string The resulting query string
     */
    public static function buildHttpQuery($class, $method = NULL, $parameters = NULL)
    {
        $url = [];
        $url['class']  = $class;
        if ($method)
        {
            $url['method'] = $method;
        }
        
        if (!empty($parameters['class']) && $parameters['class'] !== $class)
        {
            $parameters['previous_class'] = $parameters['class'];
        }
        
        if (!empty($parameters['method']) && $parameters['method'] !== $method)
        {
            $parameters['previous_method'] = $parameters['method'];
        }
        
        unset($parameters['class']);
        unset($parameters['method']);
        $query = http_build_query($url);
        $callback = self::$router;
        $short_url = null;
        
        if ($callback)
        {
            $query  = $callback($query, TRUE);
        }
        else
        {
            $query = 'index.php?'.$query;
        }
        
        if (strpos($query, '?') !== FALSE)
        {
            return $query . ( (is_array($parameters) && count($parameters)>0) ? '&'.http_build_query($parameters) : '' );
        }
        else
        {
            return $query . ( (is_array($parameters) && count($parameters)>0) ? '?'.http_build_query($parameters) : '' );
        }
    }
    
    /**
     * Recarrega a aplicação.
     */
    public static function reload()
    {
        TScript::create("__adianti_goto_page('index.php')");
    }
    
    /**
     * Registra o estado de uma página.
     * @param string $page Page URL
     */
    public static function registerPage($page)
    {
        TScript::create("__adianti_register_state('{$page}', 'user');");
    }
    
    /**
     * Tratador de erros legado do Adianti, agora aprimorado para integração.
     * Embora o ErrorHandlerSetup seja o principal ponto de captura, este método
     * serve como uma salvaguarda (fallback).
     * Se ele for chamado diretamente por algum motivo, em vez de apenas lançar
     * uma exceção, ele agora encaminha o erro diretamente para o nosso
     * sistema de tratamento centralizado, garantindo que o log e a exibição
     * ocorram conforme o esperado.
     * @param int    $errno      Error number
     * @param string $errstr     Error message
     * @param string $errfile    Error file
     * @param int    $errline    Error line
     * @return boolean
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        // Cria uma ErrorException com os detalhes do erro.
        $exception = new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        
        // Encaminha a exceção para o nosso tratador centralizado.
        ErrorHandlerSetup::handleException($exception);
        
        // Retorna true para indicar que o erro foi tratado e não deve prosseguir
        // para o tratador de erros interno do PHP.
        return true;
    }
    
    /**
     * Retorna os cabeçalhos da requisição HTTP.
     * @return array
     */
    public static function getHeaders()
    {
        $headers = array();
        foreach ($_SERVER as $key => $value)
        {
            if (substr($key, 0, 5) == 'HTTP_')
            {
                $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                $headers[$header] = $value;
            }
        }
        
        if (function_exists('getallheaders'))
        {
            $allheaders = getallheaders();
            
            if ($allheaders)
            {
                return $allheaders;
            }
            
            return $headers;
        }
        return $headers;
    }
    
    /**
     * Retorna o ID único da requisição.
     * @return string
     */
    public static function getRequestId()
    {
        return self::$request_id;
    }
    
    /**
     * Verifica se o modo de depuração está ativo.
     * @return boolean
     */
    public static function getDebugMode()
    {
        return self::$debug;
    }
}
