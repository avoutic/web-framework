<?php
####################################################################
# Global settings
#
error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

# Global configuration
#
$includes='../includes/base/';
$site_includes='../includes/site/';

# Load configuration
#
$base_config = array(
	'disabled_pages' => array(),
    'allow_registration' => true,
	'database_enabled' => false,
        'database_type' => '',
	'database_host' => '',
	'database_user' => '',
	'database_password' =>'',
	'database_database' => ''
);
if (!is_file($site_includes."config.php"))
{
	header("HTTP/1.0 500 Internal Server Error");
	print "<h1>Requirement error</h1>\n";
	print "One of the required files is not found on the server. Please contact the administrator.";
	exit(0);
}
$site_config = array();
require($site_includes."config.php");
$config = array_merge($base_config, $site_config);

# Load other prerequisites
#
require('adodb/adodb.inc.php');
require($includes.'database.inc.php');

# Check site specific preconditions
#
if (!is_file($site_includes."site_defines.inc.php") ||
    !is_file($site_includes."site_logic.inc.php") ||
	!is_file($site_includes."page_frame.inc.php"))
{
	header("HTTP/1.0 500 Internal Server Error");
	print "<h1>Requirement error</h1>\n";
	print "One of the required files is not found on the server. Please contact the administrator.";
	exit(0);
}

# Load global and site specific defines
#
require($includes."defines.inc.php");
require($site_includes."site_defines.inc.php");
require($site_includes."site_logic.inc.php");

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

####################################################################

function validate_input($filter, $item)
{
	global $state;

	if (!strlen($filter))
		die("Unexpected input: \$filter not defined in validate_input().");
	
	$str = "";
	$state['input'][$item] = "";

	if (isset($_POST[$item]))
		$str = $_POST[$item];
	else if (isset($_GET[$item]))
		$str = $_GET[$item];
	
	if (preg_match("/^\s*$filter\s*$/m", $str))
		$state['input'][$item] = stripslashes(trim($str));
}

function user_has_permissions($permissions)
{
	global $state;

	foreach ($permissions as $permission) {
		if (!in_array($permission, $state['permissions']))
				return false;
	}

	return true;
}

function set_message($type, $message, $extra_message)
{
	global $state;

	$state['input']['mtype'] = $type;
	$state['input']['message'] = $message;
	$state['input']['extra_message'] = $extra_message;
}

$fixed_page_filter = array(
	'page'	=> '[\w_]+',
	'message' => '[\w \(\)\.]+',
	'extra_message' => '[\w \(\)\.]+',
	'mtype' => 'error|info|warning|success'
);

# Start with a clean slate
#
unset($state);
$state['debug'] = false;
$state['logged_in'] = false;
$state['permissions'] = array();
$state['input'] = array();
$state['page_data'] = array();

session_start();

$database = new Database();
$database->Connect($config);

array_walk($fixed_page_filter, 'validate_input');

# Check page requested
#
$include_page = $state['input']['page'];
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
		print "Page not found. Please return to the <a href=\"?\">main page</a>.";
		exit(0);
	}
}

require($include_page_file);

$include_page_filter = get_page_filter();

array_walk($include_page_filter, 'validate_input');

# check if logged in.

if (isset($_SESSION['logged_in']))
{
	# Check status
	# TODO: CHECK LIFETIME!

	$state['logged_in'] = true;

	# Retrieve id / long name / short name
	#
	$state['user_id'] = $_SESSION['user_id'];
	$state['username'] = $_SESSION['username'];
	$state['name'] = $_SESSION['name'];
	$state['email'] = $_SESSION['email'];
	
	# Set permissions in state
	#
	$state['permissions'] = $_SESSION['permissions'];
}

$page_permissions = get_page_permissions();
$has_permissions = user_has_permissions($page_permissions);

if (!$has_permissions) {
	if (!$state['logged_in']) {
		# Redirect to login page
		header("Location: ?page=".SITE_LOGIN_PAGE."&mtype=info&message=".urlencode("Authentication required. Please login.")."&return=".urlencode($_SERVER['QUERY_STRING']));
		exit(0);
	} else {
		# Access denied
		header("HTTP/1.0 403 Access Denied");
		print "<h1>Access Denied</h1>\n";
		print "You do not have the authorization to view this page. Please return to the main page.";
		exit(0);
	}
}

# Fill content for the pages
#
$page_content = array();

do_page_logic();

fill_site_content($page_content);
$page_content['title'] = get_page_title();

# Load main frame
#
require($site_includes.'page_frame.inc.php');
?>
