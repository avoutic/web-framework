<?php
####################################################################
# Global settings
#
srand();
date_default_timezone_set('UTC');

# Load configuration
#
$base_config = array(
        'debug' => false,
        'preload' => false,
        'timezone' => 'UTC',
        'disabled_pages' => array(),
        'registration' => array(
            'allow_registration' => true,
            'email_is_username' => false,
            'after_verify_page' => '/',
        ),
        'database_enabled' => false,
        'database_config' => 'main',        // main database tag.
        'databases' => array(),             // list of extra database tags to load.
                                            // files will be retrieved from 'includes/db_config.{TAG}.php'
        'db_version' => 1,
        'server_name' => (isset($_SERVER['SERVER_NAME']))?$_SERVER['SERVER_NAME']:'app',
        'http_mode' => 'https',
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'cache_enabled' => false,
        'cache' => array(
            'cache_type' => 'memcache',       // memcache, memcached, redis
            'cache_host' => 'localhost',
            'cache_port' => '11211',
            'cache_user' => '',
            'cache_password' => ''
        ),
        'auth_mode' => 'redirect',            // redirect, www-authenticate, custom (requires auth_module)
        'auth_module' => '',
        'authenticator' => array(
            'site_login_page' => '/login',
            'default_login_return' => '/',
            'auth_required_message' => 'Authentication required. Please login.',
            'session_timeout' => 900,
        ),
        'page' => array(
            'default_frame_file' => '',
            'mods' => array()               // Should at least contain class, and include_file of mod!
        ),
        'security' => array(
            'blacklisting' => false,
            'blacklist_threshold' => 25,
            'hash' => 'sha256',
            'hmac_key' =>  'KDHAS(*&@!(*@!kjhdkjas)(*)(@*HUIHQhiuhqw',
            'crypt_key' => 'ONQifn39^&!)DMkiqnfl(!&Ala]d,lqklxoiA>W8kdvuHEWndk&6391#@yFplMaC',
        ),
        'error_handlers' => array(
            '404' => ''
        ),
        'pages' => array(
            'forgot_password' => array(
                'reset_password_page' => '/reset-password',
            ),
            'change_password' => array(
                'return_page' => '/',
            ),
            'change_email' => array(
                'return_page' => '/',
            ),
        ),
        'dispatch_mail_include' => $includes.'send_mail.inc.php',
        'sender_core' => array(
            'handler_class' => 'SenderCore',
            'default_sender' => '',
        ),
);

function log_mail($title, $content)
{
    mail(MAIL_ADDRESS, $title, $content, "From: Log Handler ".SITE_NAME." <".MAIL_ADDRESS.">\n");
}

function scrub_state(&$item)
{
    global $global_info;

    foreach ($item as $key => $value)
    {
        if (is_object($value))
            $value = $item[$key] = get_object_vars($value);

        if ($key === 0 && $value == $global_info)
            $item[$key] = 'omitted';
        else if (is_array($value))
            scrub_state($item[$key]);
        else if ($key === 'config')
            $item[$key] = 'scrubbed';
    }
}

function get_error_type_string($type)
{
    switch($type)
    {
        case E_ERROR: // 1 //
            return 'E_ERROR';
        case E_WARNING: // 2 //
            return 'E_WARNING';
        case E_PARSE: // 4 //
            return 'E_PARSE';
        case E_NOTICE: // 8 //
            return 'E_NOTICE';
        case E_CORE_ERROR: // 16 //
            return 'E_CORE_ERROR';
        case E_CORE_WARNING: // 32 //
            return 'E_CORE_WARNING';
        case E_COMPILE_ERROR: // 64 //
            return 'E_COMPILE_ERROR';
        case E_COMPILE_WARNING: // 128 //
            return 'E_COMPILE_WARNING';
        case E_USER_ERROR: // 256 //
            return 'E_USER_ERROR';
        case E_USER_WARNING: // 512 //
            return 'E_USER_WARNING';
        case E_USER_NOTICE: // 1024 //
            return 'E_USER_NOTICE';
        case E_STRICT: // 2048 //
            return 'E_STRICT';
        case E_RECOVERABLE_ERROR: // 4096 //
            return 'E_RECOVERABLE_ERROR';
        case E_DEPRECATED: // 8192 //
            return 'E_DEPRECATED';
        case E_USER_DEPRECATED: // 16384 //
            return 'E_USER_DEPRECATED';
    }

    return $type;
}

// Create a handler function
function assert_handler($file, $line, $message, $error_type, $silent = false)
{
    global $global_config, $global_state, $global_database;

    $path_parts = pathinfo($file);
    $file = $path_parts['filename'];

    $debug_message = "File '$file'\nLine '$line'\nMessage '$message'\n";
    $message = "File '$file'\nLine '$line'\n";
    $error_type = get_error_type_string($error_type);

    $state = $global_state;
    if (is_array($state))
        scrub_state($state);

    $trace = debug_backtrace();
    if (is_array($trace))
        scrub_state($trace);

    $db_error = $global_database->GetLastError();
    if ($db_error === false || $db_error === '')
	    $db_error = 'None';

    $debug_message.= "Last Database error: ".$db_error."\n";
    $debug_message.= "Backtrace:\n".print_r($trace, true);
    $debug_message.= "State:\n".print_r($state, true);

    if ($global_config['debug'])
    {
        echo "Failure information: $error_type<br/>";
        echo "<pre>";
        echo $debug_message;
        echo "</pre>";
    }
    else
    {
        if (!$silent) {
            echo "Failure information: $error_type\n";
            echo "<pre>\n";
            echo $message;
            echo "</pre>\n";
        }

        SenderCore::send_raw(MAIL_ADDRESS, 'Assertion failed',
                "Failure information: $error_type\n\nServer: ".$global_config['server_name']."\n<pre>".$debug_message.'</pre>');
    }

    if ($silent)
        exit();
    else
        die("Oops. Something went wrong. Please retry later or contact us with the information above!\n");
}

// Only go into assert_handler once
$in_verify = false;

function verify($bool, $message)
{
    if ($bool)
        return true;

    global $in_verify;
    if ($in_verify)
        exit();

    $in_verify = true;
    $bt = debug_backtrace();
    $caller = array_shift($bt);

    assert_handler($caller['file'], $caller['line'], $message, 'verify');
    exit();
}

function shutdown_handler()
{
    $last_error = error_get_last();
    if (!$last_error)
        return;

    switch($last_error['type'])
    {
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_CORE_WARNING:
        case E_COMPILE_ERROR:
        case E_COMPILE_WARNING:
            assert_handler($last_error['file'], $last_error['line'], $last_error['message'], $last_error['type']);
            break;
        default:
            assert_handler($last_error['file'], $last_error['line'], $last_error['message'], $last_error['type'], true);
    }

}

function http_error($code, $short_message, $message)
{
    header("HTTP/1.0 $code $short_message");
    print "$message";
    exit(0);
}

$hook_array = array();

function register_hook($hook_name, $file, $static_function, $args = array())
{
    global $hook_array;

    $hook_array[$hook_name][] = array(
                    'include_file' => $file,
                    'static_function' => $static_function,
                    'args' => $args);
}

function fire_hook($hook_name, $params)
{
    global $hook_array, $global_info, $site_includes, $includes;

    if (!isset($hook_array[$hook_name]))
        return;

    $hooks = $hook_array[$hook_name];
    foreach ($hooks as $hook)
    {
        require_once($site_includes.$hook['include_file'].".inc.php");

        $function = $hook['static_function'];

        $function($global_info, $hook['args'], $params);
    }
}

function urlencode_and_auth_array($array)
{
    global $global_config;

    return urlencode(encode_and_auth_array($array));
}

function encode_and_auth_array($array)
{
    global $global_config;

    $str = json_encode($array);

    # First encrypt it
    $cipher = 'AES-256-CBC';
    $iv_len = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_len);
    $key = hash('sha256', $global_config['security']['crypt_key'], TRUE);
    $str = openssl_encrypt($str, $cipher, $key, 0, $iv);

    $str = base64_encode($str);
    $iv = base64_encode($iv);

    $str_hmac = hash_hmac($global_config['security']['hash'], $iv.$str, $global_config['security']['hmac_key']);

    return $iv.":".$str.":".$str_hmac;
}

function encode_and_auth_string($str)
{
    global $global_config;

    # First encrypt it
    $cipher = 'AES-256-CBC';
    $iv_len = openssl_cipher_iv_length($cipher);
    $iv = openssl_random_pseudo_bytes($iv_len);
    $key = hash('sha256', $global_config['security']['crypt_key'], TRUE);
    $str = openssl_encrypt($str, $cipher, $key, 0, $iv);

    $str = base64_encode($str);
    $iv = base64_encode($iv);

    $str_hmac = hash_hmac($global_config['security']['hash'], $iv.$str, $global_config['security']['hmac_key']);

    return urlencode($iv.":".$str.":".$str_hmac);
}

function urldecode_and_verify_array($str)
{
    $urldecoded = urldecode($str);

    return decode_and_verify_array($urldecoded);
}

function decode_and_verify_array($str)
{
    $json_encoded = decode_and_verify_string($str);
    if (!strlen($json_encoded))
        return false;

    $array = json_decode($json_encoded, true);
    if (!is_array($array))
        return false;

    return $array;
}

function decode_and_verify_string($str)
{
    global $global_config;

    $idx = strpos($str, ":");
    if ($idx === FALSE)
        return "";

    $part_iv = substr($str, 0, $idx);
    $iv = base64_decode($part_iv);

    $str = substr($str, $idx + 1);

    $idx = strpos($str, ":", $idx);
    if ($idx === FALSE)
        return "";

    $part_msg = substr($str, 0, $idx);
    $part_hmac = substr($str, $idx + 1);

    $str_hmac = hash_hmac($global_config['security']['hash'], $part_iv.$part_msg, $global_config['security']['hmac_key']);

    if ($str_hmac !== $part_hmac)
    {
        framework_add_bad_ip_hit(5);
        return "";
    }

    $key = hash('sha256', $global_config['security']['crypt_key'], TRUE);
    $cipher = 'AES-256-CBC';
    $part_msg = openssl_decrypt(base64_decode($part_msg), $cipher, $key, 0, $iv);

    $part_msg = rtrim($part_msg. "\0");

    return $part_msg;
}

if (!is_file($site_includes."config.php"))
{
    http_error(500, 'Internal Server Error', "<h1>Requirement error</h1>\nOne of the required files is not found on the server. Please contact the administrator.");
}

# Merge configurations
#
$site_config = array();
require($site_includes."config.php");
$global_config = array_replace_recursive($base_config, $site_config);

# Enable debugging if requested
#
if ($global_config['debug'] == true)
{
    error_reporting(E_ALL | E_STRICT);
    ini_set("display_errors", 1);
}
else
{
    register_shutdown_function('shutdown_handler');
}

# Set default timezone
#
date_default_timezone_set($global_config['timezone']);

# Check for special loads before anything else
#
if ($global_config['preload'] == true)
    require_once($site_includes.'preload.inc.php');

# Load other prerequisites
#
if ($global_config['database_enabled'] == true)
    require_once($includes.'database.inc.php');

# Load global and site specific defines
#
require_once($includes."defines.inc.php");
require_once($includes."sender_core.inc.php");
require_once($includes."object_factory.inc.php");
require_once($includes."base_logic.inc.php");
require_once($includes."config_values.inc.php");

if (is_file($site_includes."site_defines.inc.php"))
    include_once($site_includes."site_defines.inc.php");

# Check if needed site defines are entered
#
if (!defined('MAIL_ADDRESS'))
	define('MAIL_ADDRESS', 'unknown@unknown.com');

if (!defined('MAIL_FOOTER'))
	define('MAIL_FOOTER', '');

if (!defined('SITE_NAME'))
	define('SITE_NAME', 'Unknown');

if (!defined('SITE_DEFAULT_PAGE'))
	define('SITE_DEFAULT_PAGE', 'main');

# Start with a clean slate
#
unset($global_state);
$global_state['debug'] = false;
$global_state['logged_in'] = false;
$global_state['permissions'] = array();
$global_state['input'] = array();
$global_state['raw_input'] = array();
$global_state['messages'] = array();

$global_state['raw_post'] = array();
$data = file_get_contents("php://input");
$data = json_decode($data, true);
if (is_array($data))
    $global_state['raw_post'] = $data;

$core_factory = new ObjectFactory();

# Start the database connection
#
$global_database = NULL;
$global_databases = array();

function get_auth_config($name)
{
    global $site_includes;

    $auth_config_file = $site_includes.'/auth/'.$name.'.php';
    $auth_config = require($auth_config_file);
    verify(is_array($auth_config) || strlen($auth_config), 'Auth Config '.$name.' invalid');

    return $auth_config;
}

if ($global_config['database_enabled'] == true)
{
    $global_database = new Database();
    $main_db_tag = $global_config['database_config'];
    $main_config = get_auth_config('db_config.'.$main_db_tag);

    if (FALSE === $global_database->Connect($main_config))
    {
        http_error(500, 'Internal Server Error', "<h1>Database server connection failed</h1>\nThe connection to the database server failed. Please contact the administrator.");
    }

    $config_values = new ConfigValues($global_database, 'db');
    $db_version = $config_values->get_value('version', '1');
    if ($db_version != $global_config['db_version'])
    {
        http_error(500, 'Internal Server Error', "<h1>Database version mismatch</h1>\nPlease contact the administrator.");
    }

    # Open auxilary database connections
    #
    foreach ($global_config['databases'] as $tag)
    {
        $global_databases[$tag] = new Database();
        $tag_config = get_auth_config('db_config.'.$tag);

        if (FALSE === $global_databases[$tag]->Connect($tag_config))
        {
            http_error(500, 'Internal Server Error', "<h1>Databases server connection failed</h1>\nThe connection to the database server failed. Please contact the administrator.");
        }
    }
}

# Start the cache connection
#
$global_cache = NULL;
if ($global_config['cache_enabled'] == true)
{
    $cache_type = $global_config['cache']['cache_type'];
    require_once($includes.'cache_'.$cache_type.'.inc.php');

    $global_cache = $core_factory->create('cache', $cache_type);

    if (FALSE === $global_cache->connect($global_config['cache']['cache_host']))
    {
        http_error(500, 'Internal Server Error', "<h1>Cache service connection failed</h1>\nThe connection to the cache service failed. Please contact the administrator.");
    }
}

function get_db($tag)
{
    global $global_info;

    if (!strlen($tag))
        return $global_info['database'];

    verify(array_key_exists($tag, $global_info["databases"]), 'Database not registered');
    return $global_info['databases'][$tag];
}

$global_info = array(
    'database' => $global_database,
    'databases' => $global_databases,
    'state' => &$global_state,
    'config' => $global_config,
    'cache' => $global_cache);
?>
