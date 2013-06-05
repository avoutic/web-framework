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
        'server_name' => (isset($_SERVER['SERVER_NAME']))?$_SERVER['SERVER_NAME']:'app',
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'cache_enabled' => false,
        'cache' => array(
            'cache_type' => 'memcache',       // memcache, memcached, redis
            'cache_host' => 'localhost',
            'cache_port' => '11211',
            'cache_user' => '',
            'cache_password' => ''
        ),
        'auth_mode' => 'redirect',            // redirect, www-authenticate
        'authenticator' => array(
            'site_login_page' => 'login',
            'after_verify_page' => '/',
            'default_login_return' => '/',
            'auth_required_message' => 'Authentication required. Please login.'
        ),
        'page' => array(
            'default_frame_file' => '',
            'mods' => array()               // Should at least contain class, and include_file of mod!
        ),
        'error_handlers' => array(
            '404' => ''
        ),
);

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);

function log_mail($title, $content)
{
    mail(MAIL_ADDRESS, $title, $content, "From: Log Handler ".SITE_NAME." <".MAIL_ADDRESS.">\n");
}

// Create a handler function
function assert_handler($file, $line, $code)
{
    global $global_config;

    $debug_message = "File '$file'\nLine '$line'\nCode '$code'\n";
    $message = "File '$file'<br />Line '$line'<br />";

    if (!$global_config['debug'] && defined('DEBUG_KEY'))
    {
        $message = bin2hex(mcrypt_cbc(MCRYPT_RIJNDAEL_128,
                    substr(DEBUG_KEY, 0, 32),
                           $message,
                           MCRYPT_ENCRYPT,
                           substr(DEBUG_KEY, 32, 16)));
        $message = implode('<br />', str_split($message, 32));
    }

    $debug_message.= print_r(debug_backtrace(), true);

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

if (!is_file($site_includes."config.php"))
{
    http_error(500, 'Internal Server Error', "<h1>Requirement error</h1>\nOne of the required files is not found on the server. Please contact the administrator.");
}

# Merge configurations
#
$site_config = array();
require($site_includes."config.php");
$global_config = array_merge($base_config, $site_config);

# Enable debugging if requested
#
if ($global_config['debug'] == true)
{
    error_reporting(E_ALL | E_STRICT);
    ini_set("display_errors", 1);
}

# Load other prerequisites
#
if ($global_config['database_enabled'] == true)
{
    require('adodb/adodb.inc.php');
    require($includes.'database.inc.php');
}

# Load global and site specific defines
#
require($includes."defines.inc.php");
require($includes."object_factory.inc.php");
require($includes."base_logic.inc.php");

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

session_start();
$core_factory = new ObjectFactory();

# Start the database connection
#
$global_database = NULL;
if ($global_config['database_enabled'] == true)
{
    $global_database = new Database();
    if (FALSE === $global_database->Connect($global_config['database']))
    {
        http_error(500, 'Internal Server Error', "<h1>Database server connection failed</h1>\nThe connection to the database server failed. Please contact the administrator.");
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

$global_info = array(
    'database' => $global_database,
    'state' => &$global_state,
    'config' => $global_config,
    'cache' => $global_cache);
?>
