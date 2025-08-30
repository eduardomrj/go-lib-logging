<?php
namespace Adianti\Core;

use Error;
use Exception;
use ErrorException;
use ReflectionClass;
use ReflectionMethod;
use Adianti\Control\TPage;
use Mad\Service\MadLogService;
use Adianti\Widget\Base\TScript;
use Adianti\Widget\Dialog\TMessage;
use Adianti\Core\AdiantiCoreTranslator;
use Adianti\Widget\Util\TExceptionView;

/**
 * Basic structure to run a web application
 *
 * Provides the core structure for executing web applications within the Adianti Framework.
 * Handles request execution, error handling, routing, and application flow.
 *
 * @version    7.5
 * @package    core
 * @author     Pablo Dall'Oglio
 * @copyright  Copyright (c) 2006 Adianti Solutions Ltd. (http://www.adianti.com.br)
 * @license    http://www.adianti.com.br/framework-license
 */
class AdiantiCoreApplication
{
    private static $router;
    private static $request_id;
    private static $debug;
    
    /**
     * Executes the requested class and method based on HTTP request parameters.
     * Handles errors and exceptions, and manages the application flow.
     *
     * @param bool $debug Whether to enable detailed debugging for exceptions.
     */
    public static function run($debug = FALSE)
    {
        self::$request_id = uniqid();
        self::$debug = $debug;

        MadLogService::initializeDebugLogging();
        
        $ini = AdiantiApplicationConfig::get();
        $service = isset($ini['general']['request_log_service']) ? $ini['general']['request_log_service'] : '\SystemRequestLogService';
        $class   = isset($_REQUEST['class'])    ? $_REQUEST['class']   : '';
        $static  = isset($_REQUEST['static'])   ? $_REQUEST['static']  : '';
        $method  = isset($_REQUEST['method'])   ? $_REQUEST['method']  : '';
        
        $content = '';
        set_error_handler(array('AdiantiCoreApplication', 'errorHandler'));
        
        if (!empty($ini['general']['request_log']) && $ini['general']['request_log'] == '1')
        {
            if (empty($ini['general']['request_log_types']) || strpos($ini['general']['request_log_types'], 'web') !== false)
            {
                self::$request_id = $service::register( 'web');
            }
        }
        
        self::filterInput();
        
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
        else if (class_exists($class))
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
                try
                {
                    $page = new $class( $_REQUEST );
                    
                    ob_start();
                    $page->show( $_REQUEST );
	                $content = ob_get_contents();
	                ob_end_clean();
                }
                catch (Exception $e)
                {
                    ob_start();
                    if ($debug)
                    {
                        new TExceptionView($e);
                        $content = ob_get_contents();
                    }
                    else
                    {
                        new TMessage('error', $e->getMessage());
                        $content = ob_get_contents();
                    }
                    ob_end_clean();
                }
                catch (Error $e)
                {
                    
                    ob_start();
                    if ($debug)
                    {
                        new TExceptionView($e);
                        $content = ob_get_contents();
                    }
                    else
                    {
                        new TMessage('error', $e->getMessage());
                        $content = ob_get_contents();
                    }
                    ob_end_clean();
                }
            }
        }
        else if (!empty($class))
        {
            new TMessage('error', AdiantiCoreTranslator::translate('Class ^1 not found', " <b><i><u>{$class}</u></i></b>") . '.<br>' . AdiantiCoreTranslator::translate('Check the class name or the file name').'.');
        }
        
        if (!$static)
        {
            echo TPage::getLoadedCSS();
        }
        echo TPage::getLoadedJS();

        MadLogService::finalizeDebugLogging();
        
        echo $content;
    }
    
    /**
     * Executes an internal method of a specified class with provided parameters.
     *
     * @param string      $class     The class name to be executed.
     * @param string      $method    The method name to be executed.
     * @param array       $request   The request parameters.
     * @param string|null $endpoint  The request endpoint for logging purposes (optional).
     *
     * @return mixed The response from the executed method.
     * @throws Exception If the class or method is not found or cannot be executed.
     */
    public static function execute($class, $method, $request, $endpoint = null)
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
    
    /**
     * Filters request inputs to prevent execution of unauthorized SQL commands.
     * Ensures security by sanitizing potentially dangerous input values.
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
     * Sets a custom router callback for handling application routes.
     *
     * @param callable $callback The routing callback function.
     */
    public static function setRouter(Callable $callback)
    {
        self::$router = $callback;
    }
    
    /**
     * Retrieves the currently set router callback.
     *
     * @return callable|null The registered router callback, or null if not set.
     */
    public static function getRouter()
    {
        return self::$router;
    }
    
    /**
     * Executes a method of a specified class with given parameters.
     * Redirects execution to the gotoPage method.
     *
     * @param string      $class      The class name.
     * @param string|null $method     The method name (optional).
     * @param array|null  $parameters Additional parameters (optional).
     */
    public static function executeMethod($class, $method = NULL, $parameters = NULL)
    {
        self::gotoPage($class, $method, $parameters);
    }
    
    /**
     * Processes the application request and inserts the output into a given template.
     *
     * @param string $template The HTML template where content should be inserted.
     *
     * @return string The processed template with dynamic content.
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
     * Redirects the application to a specific page using JavaScript navigation.
     *
     * @param string      $class      The class name to navigate to.
     * @param string|null $method     The method to be called (optional).
     * @param array|null  $parameters Additional parameters (optional).
     * @param callable|null $callback Custom callback function for navigation (optional).
     */
    public static function gotoPage($class, $method = NULL, $parameters = NULL, $callback = NULL)
    {
        unset($parameters['static']);
        $query = self::buildHttpQuery($class, $method, $parameters);
        
        TScript::create("__adianti_goto_page('{$query}');", true, 1);
    }
    
    /**
     * Loads a specific page within the application using JavaScript.
     *
     * @param string      $class      The class name to load.
     * @param string|null $method     The method to be called (optional).
     * @param array|null  $parameters Additional parameters (optional).
     */
    public static function loadPage($class, $method = NULL, $parameters = NULL)
    {
        $query = self::buildHttpQuery($class, $method, $parameters);
        
        TScript::create("__adianti_load_page('{$query}');", true, 1);
    }
    
    /**
     * Loads a page by a specified URL using JavaScript.
     *
     * @param string $query The URL query string for loading the page.
     */
    public static function loadPageURL($query)
    {
        TScript::create("__adianti_load_page('{$query}');", true, 1);
    }
    
    /**
     * Sends form data via JavaScript to a specified class and method.
     *
     * @param string      $formName   The name of the form to submit.
     * @param string      $class      The target class.
     * @param string|null $method     The method to be executed (optional).
     * @param array|null  $parameters Additional parameters (optional).
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
     * Constructs an HTTP query string based on class, method, and parameters.
     *
     * @param string      $class      The target class.
     * @param string|null $method     The method to be executed (optional).
     * @param array|null  $parameters Additional parameters (optional).
     *
     * @return string The constructed HTTP query string.
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
     * Reloads the current application by redirecting to the main index page.
     */
    public static function reload()
    {
        TScript::create("__adianti_goto_page('index.php')");
    }
    
    /**
     * Registers a page state for tracking in JavaScript.
     *
     * @param string $page The page URL to be registered.
     */
    public static function registerPage($page)
    {
        TScript::create("__adianti_register_state('{$page}', 'user');");
    }
    
    /**
     * Handles recoverable errors and converts them into exceptions.
     *
     * @param int    $errno   The error number.
     * @param string $errstr  The error message.
     * @param string $errfile The file where the error occurred.
     * @param int    $errline The line number of the error.
     *
     * @return bool Returns false to continue with PHP's default error handler.
     * @throws ErrorException If the error is recoverable.
     */
    public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        if ( $errno === E_RECOVERABLE_ERROR )
        {
            throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
        }
        
        return false;
    }
    
    /**
     * Retrieves all HTTP request headers.
     *
     * @return array The associative array of HTTP headers.
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
     * Shows a page with the given class and method.
     *
     * @param string $class The class name.
     * @param string|null $method The method name (optional).
     * @param array|null $parameters Additional parameters (optional).
     */

    public static function showPage($class, $method = NULL, $parameters = NULL)
    {
        $page = new $class($parameters);
        
        if($method)
        {
            $page->$method($parameters);
        }
        
        $page->setTargetContainer($parameters['target_container'] ?? 'adianti_div_content');
        
        $page->setProperty('class', '');
        $page->setIsWrapped(true);
        $page->show();
    }
    
    /**
     * Retrieves the unique request ID assigned to the current execution.
     *
     * @return string The unique request ID.
     */
    public static function getRequestId()
    {
        return self::$request_id;
    }
    
    /**
     * Checks whether debug mode is enabled.
     *
     * @return bool True if debug mode is enabled, false otherwise.
     */
    public static function getDebugMode()
    {
        return self::$debug;
    }
}
