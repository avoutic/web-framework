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
        'allow_registration' => true,
        'database_enabled' => false,
        'database' => array(
            'database_type' => '',
            'database_host' => '',
            'database_user' => '',
            'database_password' =>'',
            'database_database' => ''
        ),
        'db_version' => 1,
        'server_name' => (isset($_SERVER['SERVER_NAME']))?$_SERVER['SERVER_NAME']:'app',
        'http_mode' => 'http',
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
            'site_login_page' => 'login',
            'after_verify_page' => '/',
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
        'dispatch_mail_include' => $includes.'send_mail.inc.php',
);

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

function log_mail($title, $content)
{
    mail(MAIL_ADDRESS, $title, $content, "From: Log Handler ".SITE_NAME." <".MAIL_ADDRESS.">\n");
}

function scrub_state(&$item)
{
    foreach ($item as $key => $value)
    {
        if (is_object($value))
            $value = $item[$key] = get_object_vars($value);

        if (is_array($value))
            scrub_state($item[$key]);

        if ($key === 'config')
            $item[$key] = 'scrubbed';
    }
}

// Create a handler function
function assert_handler($file, $line, $code)
{
    global $global_config, $global_state;

    $debug_message = "File '$file'\nLine '$line'\nCode '$code'\n";
    $message = "File '$file'<br />Line '$line'<br />";

    $state = $global_state;
    if (is_array($state))
        scrub_state($state);

    $trace = debug_backtrace();
    if (is_array($trace))
        scrub_state($trace);

    $debug_message.= 'State:\n'.print_r($state, true);
    $debug_message.= print_r($trace, true);

    if ($global_config['debug'])
    {
        echo "Failure information:<br/>";
        echo "<pre>";
        echo $debug_message;
        echo "</pre>";
    }
    else
    {
        echo "Failure information:<br/>";
        echo "<pre>";
        echo $message;
        echo "</pre>";


        log_mail('Assertion failed',
                "Failure information:\n\nServer: ".$global_config['server_name']."\n".$debug_message);
    }

    die('Oops. Something went wrong. Please retry later or contact us with the information above!');
}

assert_options(ASSERT_CALLBACK, 'assert_handler');

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

        $function($global_info, $params);
    }
}

function encode_and_auth_string($str)
{
    global $global_config;

    # First encrypt it
    $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC), MCRYPT_RAND);
    $key = hash('sha256', $global_config['security']['crypt_key'], TRUE);
    $str = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $str, MCRYPT_MODE_CBC, $iv);

    $str = base64_encode($str);
    $iv = base64_encode($iv);

    $str_hmac = hash_hmac($global_config['security']['hash'], $iv.$str, $global_config['security']['hmac_key']);

    return urlencode($iv.":".$str.":".$str_hmac);
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
    $part_msg = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, base64_decode($part_msg), MCRYPT_MODE_CBC, $iv);

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

if ($global_config['database_enabled'] == true)
{
    $global_database = new Database();
    if (FALSE === $global_database->Connect($global_config['database']))
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
    foreach ($global_config['databases'] as $key => $value)
    {
        $global_databases[$key] = new Database();

        if (FALSE === $global_databases[$key]->Connect($value))
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

    assert('array_key_exists($tag, $global_info["databases"]) /* Database not registered */');
    return $global_info['databases'][$tag];
}

$global_info = array(
    'database' => $global_database,
    'databases' => $global_databases,
    'state' => &$global_state,
    'config' => $global_config,
    'cache' => $global_cache);
?>
