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
        'server_name' => $_SERVER['SERVER_NAME'],
        'document_root' => $_SERVER['DOCUMENT_ROOT'],
        'memcache_enabled' => false,
        'memcache_host' => 'localhost',
        'memcache_port' => '11211',
        'default_frame_file' => ''
);

function http_error($code, $short_message, $message)
{
    header("HTTP/1.0 $code $short_message");
    print "$message";
    exit(0);
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

# Check site specific preconditions
#
if (!is_file($site_includes."site_defines.inc.php"))
{
    http_error(500, 'Internal Server Error', "<h1>Requirement error</h1>\nOne of the required files is not found on the server. Please contact the administrator.");
}

if (!strlen($config['default_frame_file']))
{
    http_error(500, 'Internal Server Error', "<h1>Requirement error</h1>\nOne of the required configuration settings is not found on the server. Please contact the administrator.");
}

# Load global and site specific defines
#
require($includes."defines.inc.php");
require($site_includes."site_defines.inc.php");

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

if (!defined('SITE_LOGIN_PAGE'))
	define('SITE_LOGIN_PAGE', 'login');

if (!defined('DEFAULT_LOGIN_RETURN'))
	define('DEFAULT_LOGIN_RETURN', 'main');

if(!defined('AUTHENTICATION_REQUIRED_MESSAGE'))
    define('AUTHENTICATION_REQUIRED_MESSAGE', 'Authentication required. Please login.');

####################################################################

function validate_input($filter, $item)
{
	global $global_state;

	if (!strlen($filter))
		die("Unexpected input: \$filter not defined in validate_input().");
	
	$str = "";
	$global_state['input'][$item] = "";

	if (isset($_POST[$item]))
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

	$global_state['message']['mtype'] = $type;
	$global_state['message']['message'] = $message;
	$global_state['message']['extra_message'] = $extra_message;
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
$global_state['message'] = array();
$global_state['page_data'] = array();

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

$global_state['message']['mtype'] = $global_state['input']['mtype'];
$global_state['message']['message'] = $global_state['input']['message'];
$global_state['message']['extra_message'] = $global_state['input']['extra_message'];

# Check if logged in.
#
if (isset($_SESSION['logged_in']))
{
	# Check status
	# TODO: CHECK LIFETIME!

	$global_state['logged_in'] = true;

	# Retrieve id / long name / short name
	#
	$global_state['user_id'] = $_SESSION['user_id'];
	$global_state['username'] = $_SESSION['username'];
	$global_state['name'] = $_SESSION['name'];
	$global_state['email'] = $_SESSION['email'];

	if (isset($_SESSION['first_name']))
        $global_state['first_name'] = $_SESSION['first_name'];
	
    if (isset($_SESSION['last_name']))
        $global_state['last_name'] = $_SESSION['last_name'];
	
	# Set permissions in state
	#
	$global_state['permissions'] = $_SESSION['permissions'];
}

# Check page requested
#
$include_page = $global_state['input']['page'];
if (!$include_page && isset($_GET['page']) && strlen($_GET['page']))
{
    header("HTTP/1.0 404 Not Found");
    print "<h1>Page not found</h1>\n";
    print "Page not found. Please return to the <a href=\"/\">main page</a>.";
    exit(0);
}

if (!$include_page) $include_page = SITE_DEFAULT_PAGE;

# Check if page is allowed
#
if (in_array($include_page, $config['disabled_pages'])) {
	header("HTTP/1.0 403 Page disabled");
	print "<h1>Page has been disabled</h1>\n";
	print "This page has been disabled. Please return to the main page.";
	exit(0);
}

$include_page_file = $site_includes.$include_page.".inc.php";
if (!is_file($include_page_file)) {
	$include_page_file = $includes.$include_page.".inc.php";
	if (!is_file($include_page_file)) {
		header("HTTP/1.0 404 Not Found");
		print "<h1>Page not found</h1>\n";
		print "Page not found. Please return to the <a href=\"/\">main page</a>.";
		exit(0);
	}
}

require($includes.'page_basic.inc.php');
require($include_page_file);

$object_name = preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')", 'page_'.$include_page);
$include_page_filter = NULL;
$page_permissions = NULL;
$page_obj = NULL;

if (class_exists($object_name))
{
    $include_page_filter = $object_name::get_filter();
    $page_permissions = $object_name::get_permissions();
}
else
{
    http_error(500, 'Internal Server Error', "<h1>Object not found</h1>\nThe requested object could not be located. Please contact the administrator.");
}

if (!is_array($include_page_filter))
    die('Unexpected return value');

array_walk($include_page_filter, 'validate_input');

$has_permissions = user_has_permissions($page_permissions);

if (!$has_permissions) {
	if (!$global_state['logged_in']) {
		# Redirect to login page
        $query = $_SERVER['QUERY_STRING'];

        if (substr($query, 0, 5) != 'page=')
            http_error(500, 'Internal Server Error', '<h1>Unauthorized call to authorized page</h1>\nThe call order was wrong. Please contact the administrator.');
        
        $pos = strpos($query, '&');
        if ($pos !== FALSE)
            $query = substr($query, $pos);
        else
            $query = "";

		header("Location: /".SITE_LOGIN_PAGE."?mtype=info&message=".urlencode(AUTHENTICATION_REQUIRED_MESSAGE)."&return_page=".urlencode($include_page)."&return_query=".urlencode($query));
		exit(0);
	} else {
		# Access denied
		header("HTTP/1.0 403 Access Denied");
		print "<h1>Access Denied</h1>\n";
		print "You do not have the authorization to view this page. Please return to the main page.";
		exit(0);
	}
}

$page_obj = new $object_name($global_database, $global_state, $config['default_frame_file']);
$page_obj->do_logic();
unset($global_state['input']);
$page_obj->display_frame();
?>
