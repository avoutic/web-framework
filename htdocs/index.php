<?php
####################################################################
# Global settings
#
srand();
date_default_timezone_set('UTC');

# Global configuration
#
$includes='../includes/base/';
$site_includes='../includes/site/';

# Load configuration
#
$base_config = array(
        'debug' => false,
        'disabled_pages' => array(),
        'allow_registration' => true,
        'database_enabled' => false,
        'database_type' => '',
        'database_host' => '',
        'database_user' => '',
        'database_password' =>'',
        'database_database' => '',
        'auth_mode' => 'form',            // form, oauth or www-authenticate
        'server_name' => $_SERVER['SERVER_NAME'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'memcache_enabled' => false,
        'memcache_host' => 'localhost',
        'memcache_port' => '11211',
        'authenticator' => array(
            'site_login_page' => 'login',
            'default_login_return' => 'main',
            'auth_required_message' => 'Authentication required. Please login.'
        ),
        'page' => array(
            'default_frame_file' => ''
        )
);

function http_error($code, $short_message, $message)
{
    header("HTTP/1.0 $code $short_message");
    print "$message";
    exit(0);
}

function send_404()
{
    http_error(404, 'Not Found', '<h1>Page not found</h1>\nPage not found. Please return to the <a href="/">main page</a>.');
}

if (!is_file($site_includes."config.php"))
{
    http_error(500, 'Internal Server Error', "<h1>Requirement error</h1>\nOne of the required files is not found on the server. Please contact the administrator.");
}

# Merge configurations
#
$site_config = array();
require($site_includes."config.php");
$config = array_merge($base_config, $site_config);

# Enable debugging if requested
#
if ($config['debug'] == true)
{
    error_reporting(E_ALL | E_STRICT);
    ini_set("display_errors", 1);
}

# Load other prerequisites
#
if ($config['database_enabled'] == true)
{
    require('adodb/adodb.inc.php');
    require($includes.'database.inc.php');
}

# Load global and site specific defines
#
require($includes."defines.inc.php");

if (is_file($site_includes."site_defines.inc.php"))
    include_once($site_includes."site_defines.inc.php");

# Load route array if available
#
$route_array = array();

if (is_file($site_includes."site_logic.inc.php"))
    include_once($site_includes."site_logic.inc.php");
 
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

####################################################################

function validate_input($filter, $item)
{
	global $global_state;

	if (!strlen($filter))
		die("Unexpected input: \$filter not defined in validate_input().");
	
	$str = "";
	$global_state['input'][$item] = "";

    if (isset($global_state['raw_post'][$item]))
        $str = $global_state['raw_post'][$item];
	else if (isset($_POST[$item]))
		$str = $_POST[$item];
	else if (isset($_GET[$item]))
		$str = $_GET[$item];
	
	if (preg_match("/^\s*$filter\s*$/m", $str))
		$global_state['input'][$item] = stripslashes(trim($str));
}

function user_has_permissions($permissions)
{
	global $global_state;

	foreach ($permissions as $permission) {
		if (!in_array($permission, $global_state['permissions']))
				return false;
	}

	return true;
}

function set_message($type, $message, $extra_message)
{
	global $global_state;

    array_push($global_state['messages'], array(
        'mtype' => $type,
        'message' => $message,
        'extra_message' => $extra_message));
}

$fixed_page_filter = array(
	'page'	=> '[\w_\/]+',
	'message' => '[\w \(\)\.\-!\?]+',
	'extra_message' => '[\w \(\)\.\-!\?]+',
	'mtype' => 'error|info|warning|success'
);

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

# Start the database connection
#
$global_database = NULL;
if ($config['database_enabled'] == true)
{
    $global_database = new Database();
    $global_database->Connect($config);
    if (FALSE === $global_database->Connect($config))
    {
        http_error(500, 'Internal Server Error', "<h1>Database server connection failed</h1>\nThe connection to the database server failed. Please contact the administrator.");
    }
}

# Start the memcache connection
#
$memcache = NULL;
if ($config['memcache_enabled'] == true)
{
    $memcache = new Memcache();
    if (FALSE === $memcache->connect($config['memcache_host']))
    {
        http_error(500, 'Internal Server Error', "<h1>Memcache server connection failed</h1>\nThe connection to the memcache server failed. Please contact the administrator.");
    }
}

array_walk($fixed_page_filter, 'validate_input');

if (strlen($global_state['input']['mtype']))
    set_message($global_state['input']['mtype'], $global_state['input']['message'], $global_state['input']['extra_message']);

# Create Authenticator
#
require($includes.'auth.inc.php');

$authenticator = null;
if ($config['auth_mode'] == 'form')
    $authenticator = new AuthForm($global_database, $config['authenticator']);
else if ($config['auth_mode'] == 'www-authenticate')
    $authenticator = new AuthWwwAuthenticate($global_database, $config['authenticator']);
else
    die('No valid authenticator found.');

# Check if logged in.
#
$logged_in = $authenticator->get_logged_in();

if ($logged_in !== FALSE)
{
    $global_state['auth'] = $logged_in;
	$global_state['logged_in'] = true;

	# Retrieve id / long name / short name
	#
	$global_state['user_id'] = $global_state['auth']['user_id'];
	$global_state['username'] = $global_state['auth']['username'];
	$global_state['name'] = $global_state['auth']['name'];
	$global_state['email'] = $global_state['auth']['email'];

	# Set permissions in state
	#
	$global_state['permissions'] = $global_state['auth']['permissions'];
}

# Check page requested
#
$request_uri = '/';
if (isset($_SERVER['REDIRECT_URL']))
    $request_uri = $_SERVER['REDIRECT_URL'];

$global_state['request_uri'] = $request_uri;

$request_uri = $_SERVER['REQUEST_METHOD'].' '.$request_uri;

if (!preg_match("/^\w+ [\w\-_\/]+$/m", $request_uri))
    send_404();

# Check if there is a route to follow
#
$target_info = null;
foreach ($route_array as $route => $target)
{
    if (preg_match("!^$route$!", $request_uri, $matches))
    {
        $target_info = $target;
        break;
    }
}

$include_page = "";
if ($target_info != null)
{
    $include_page = $target_info['include_file'];
}
else
{
    $include_page = $global_state['input']['page'];
    if (!$include_page && isset($_GET['page']) && strlen($_GET['page']))
        send_404();
    $matches = null;
}

if (!$include_page) $include_page = SITE_DEFAULT_PAGE;

# Check if page is allowed
#
if (in_array($include_page, $config['disabled_pages']))
    $authenticator->show_disabled();

$include_page_file = $site_includes.$include_page.".inc.php";
if (!is_file($include_page_file)) {
	$include_page_file = $includes.$include_page.".inc.php";

	if (!is_file($include_page_file))
		send_404();
}

require($includes.'page_basic.inc.php');
require($include_page_file);

$object_name = "";
$function_name = "";

if ($target_info != null)
{
    $target = explode('.', $target_info['class']);
    if (count($target) != 2)
        die('Illegal target name.');

    $object_name = $target[0];
    $function_name = $target[1];
}
else
{
    $object_name = preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')", 'page_'.$include_page);
    $function_name = "html_main";
}

$include_page_filter = NULL;
$page_permissions = NULL;
$page_obj = NULL;

if (!class_exists($object_name))
    http_error(500, 'Internal Server Error', "<h1>Object not found</h1>\nThe requested object could not be located. Please contact the administrator.");

$include_page_filter = $object_name::get_filter();
$page_permissions = $object_name::get_permissions();

if (!is_array($include_page_filter))
    die('Unexpected return value');

array_walk($include_page_filter, 'validate_input');

$has_permissions = user_has_permissions($page_permissions);

if (!$has_permissions) {
	if (!$global_state['logged_in']) {
        $authenticator->redirect_login($include_page);
		exit(0);
	} else {
        $authenticator->access_denied();
		exit(0);
	}
}

$page_obj = new $object_name($global_database, $global_state, $config['page']);
$argument_count = 0;
if (is_array($matches))
    $argument_count = count($matches) - 1;

if ($argument_count == 0)
    $page_obj->$function_name();
else if ($argument_count == 1)
    $page_obj->$function_name($matches[1]);
else
    echo "No method for $argument_count yet..\n";
?>
