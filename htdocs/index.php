<?php
# Global configuration
#
$includes='../web_framework/includes/';
$site_includes='../includes/';
$site_views='../views/';

require_once($includes.'wf_core.inc.php');

function send_404()
{
    global $global_info, $site_views;

    if (strlen($global_info['config']['error_handlers']['404']))
    {
        $include_page = $global_info['config']['error_handlers']['404'];
        $include_page_file = $site_views.$include_page.".inc.php";

        require_once($include_page_file);

        $object_name = preg_replace('/(?:^|[_\-])(.?)/e',"strtoupper('$1')", 'page_'.$include_page);
        $function_name = "html_main";

        header("HTTP/1.0 404 Page not found");
        call_obj_func($global_info, $object_name, $function_name);
        exit(0);
    }

    http_error(404, 'Not Found', "<h1>Page not found</h1>\nPage not found. Please return to the <a href=\"/\">main page</a>.");
}

require_once($includes.'page_basic.inc.php');

function validate_input($filter, $item)
{
	global $global_state;

	if (!strlen($filter))
		die("Unexpected input: \$filter not defined in validate_input().");

    if (substr($item, -2) == '[]')
    {
        $item = substr($item, 0, -2);

        // Expect multiple values
        //
    	$info = array();
	    $global_state['input'][$item] = array();

        if (isset($global_state['raw_post'][$item]))
            $info = $global_state['raw_post'][$item];
    	else if (isset($_POST[$item]))
	    	$info = $_POST[$item];
    	else if (isset($_GET[$item]))
	    	$info = $_GET[$item];

        foreach ($info as $k => $val)
            if (preg_match("/^\s*$filter\s*$/m", $val))
                $global_state['input'][$item][$k] = trim($val);
    }
    else
    {
    	$str = "";
	    $global_state['input'][$item] = "";

        if (isset($global_state['raw_post'][$item]))
            $str = $global_state['raw_post'][$item];
    	else if (isset($_POST[$item]))
	    	$str = $_POST[$item];
    	else if (isset($_GET[$item]))
	    	$str = $_GET[$item];

    	if (preg_match("/^\s*$filter\s*$/m", $str))
	    	$global_state['input'][$item] = trim($str);
    }
}

function user_has_permissions($permissions)
{
	global $global_state;

    if (count($permissions) == 0)
        return true;

    if ($global_state['logged_in'] == false)
        return false;

	foreach ($permissions as $permission) {
        if ($permission == 'logged_in')
            continue;

        if (!$global_state['user']->has_right($permission))
            return false;
	}

	return true;
}

function enforce_permissions($object_name, $permissions)
{
    global $authenticator, $global_info;

    $has_permissions = user_has_permissions($permissions);

    if ($has_permissions)
        return;

    if (!$global_info['state']['logged_in'])
    {
        $redirect_type = $object_name::redirect_login_type();
        $request_uri = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
        $authenticator->redirect_login($redirect_type, $request_uri);
        exit(0);
    }

    $authenticator->access_denied($global_info['config']['authenticator']['site_login_page']);
    exit(0);
}

function set_message($type, $message, $extra_message)
{
	global $global_state;

    array_push($global_state['messages'], array(
        'mtype' => $type,
        'message' => $message,
        'extra_message' => $extra_message));
}

function add_message_to_url($mtype, $message, $extra_message = '')
{
    $msg = array('mtype' => $mtype, 'message' => $message, 'extra_message' => $extra_message);
    $msg_str = json_encode($msg);
    return "msg=".encode_and_auth_string($msg_str);
}

function add_message_from_url($url_str)
{
    $str = decode_and_verify_string($url_str);
    if (!strlen($str))
        return;

    $msg = json_decode($str, true);

    set_message($msg['mtype'], $msg['message'], $msg['extra_message']);
}

function register_route($regex, $file, $class_function, $args = array())
{
    global $route_array;

    array_push($route_array, array(
                    'type' => 'route',
                    'regex' => $regex,
                    'include_file' => $file,
                    'class' => $class_function,
                    'args' => $args));
}

function register_redirect($regex, $redirect, $type = '301', $args = array())
{
    global $route_array;

    array_push($route_array, array(
                    'type' => 'redirect',
                    'regex' => $regex,
                    'redirect' => $redirect,
                    'redir_type' => $type,
                    'args' => $args));
}

function get_csrf_token()
{
    if (!isset($_SESSION['csrf_token']) || strlen($_SESSION['csrf_token']) != 16)
        $_SESSION['csrf_token'] = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);

    $token = $_SESSION['csrf_token'];
    $xor = mcrypt_create_iv(16, MCRYPT_DEV_URANDOM);
    for ($i = 0; $i < 16; $i++)
        $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));

    return bin2hex($xor).bin2hex($token);
}

function validate_csrf_token()
{
    global $global_state;

    if(!isset($_SESSION['csrf_token']))
        return FALSE;

    $check = $_SESSION['csrf_token'];
    $value = $global_state['input']['token'];
    if (strlen($value) != 16 * 4 || strlen($check) != 16)
        return;

    $xor = pack("H*" , substr($value, 0, 16 * 2));
    $token = pack("H*", substr($value, 16 * 2, 16 * 2));

    // Slow compare (time-constant)
    $diff = 0;
    for ($i = 0; $i < 16; $i++)
    {
        $token[$i] = chr(ord($xor[$i]) ^ ord($token[$i]));
        $diff |= ord($token[$i]) ^ ord($check[$i]);
    }

    return ($diff === 0);
}

function framework_add_bad_ip_hit($amount = 1)
{
    global $global_database, $global_config;

    if (!$global_config['security']['blacklisting'])
        return;

    $result = $global_database->Query('DELETE FROM ip_list WHERE last_hit < DATE_SUB(NOW(), INTERVAL 4 HOUR)', array());
    assert('$result !== FALSE /* Failed to clean up hit_list */');

    $result = $global_database->Query('INSERT INTO ip_list VALUES(inet_aton(?), 1, now()) ON DUPLICATE KEY UPDATE hits = hits + ?', array($_SERVER['REMOTE_ADDR'], $amount));
    assert('$result !== FALSE /* Failed to update hit_list */');
}

function check_blacklisted()
{
    global $global_database, $global_config;

    if (!$global_config['security']['blacklisting'])
        return;

    $result = $global_database->Query('DELETE FROM ip_list WHERE last_hit < DATE_SUB(NOW(), INTERVAL 4 HOUR)', array());
    assert('$result !== FALSE /* Failed to clean up hit_list */');

    $result = $global_database->Query('SELECT * FROM ip_list WHERE ip = inet_aton(?) AND hits > ?', array($_SERVER['REMOTE_ADDR'], $global_config['security']['blacklist_threshold']));
    assert('$result !== FALSE /* Failed to read hit_list */');

    if ($result->RecordCount() != 1)
        return FALSE;

    return TRUE;
}

session_name(preg_replace('/\./', '_', $global_config['server_name']));
session_set_cookie_params(0, '/', $global_config['server_name'], $global_config['http_mode'] === 'https', true);
session_start();

// Check blacklist
//
if (check_blacklisted())
    die('Blacklisted');

// Add random header (against BREACH like attacks)
//
header('X-Random:'. substr(sha1(time()), 0, rand(1, 40)));

// Add Clickjack prevention header
//
header('X-Frame-Options: DENY');

$fixed_page_filter = array(
        'page'	=> '[\w\._\-\/]+',
        'msg' => '.*',
        'token' => '.*',
        'do' => 'yes|preview',
);

array_walk($fixed_page_filter, 'validate_input');

if (strlen($global_state['input']['msg']))
    add_message_from_url($global_state['input']['msg']);

if (strlen($global_state['input']['do']))
{
    if (!validate_csrf_token())
    {
        $global_state['input']['do'] = '';
        framework_add_bad_ip_hit();
        set_message('error', 'CSRF token missing, possible attack.', '');
    }
}

# Load route and hooks array and site specific logic if available
#

if (is_file($site_includes."site_logic.inc.php"))
    include_once($site_includes."site_logic.inc.php");

$route_array = array();

if (function_exists('register_hooks'))
    register_hooks();

if (function_exists('register_routes'))
    register_routes();

# Create Authenticator
#
require($includes.'auth.inc.php');

$authenticator = null;
if ($global_config['auth_mode'] == 'redirect')
    $authenticator = new AuthRedirect($global_info);
else if ($global_config['auth_mode'] == 'www-authenticate')
    $authenticator = new AuthWwwAuthenticate($global_info);
else
    die('No valid authenticator found.');

# Clean stale sessions
$authenticator->cleanup();

$global_info['auth'] = $authenticator;

# Check if logged in and populate standard fields.
#
$logged_in = $authenticator->get_logged_in();

if ($logged_in !== FALSE)
{
    $global_state['auth'] = $logged_in;
    $global_state['logged_in'] = true;

    # Retrieve id / long name / short name
    #
    $global_state['user'] = $global_state['auth']['user'];
    $global_state['user_id'] = $global_state['auth']['user_id'];
    $global_state['username'] = $global_state['auth']['username'];
    $global_state['name'] = $global_state['auth']['name'];
    $global_state['email'] = $global_state['auth']['email'];
}

# Check page requested
#
$request_uri = '/';

if (isset($_SERVER['REQUEST_URI']))
    $request_uri = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);

$global_state['request_uri'] = $request_uri;

$request_uri = $_SERVER['REQUEST_METHOD'].' '.$request_uri;

# Check if there is a route to follow
#
$target_info = null;
foreach ($route_array as $target)
{
    $route = $target['regex'];
    if (preg_match("!^$route$!", $request_uri, $matches))
    {
        $target_info = $target;
        break;
    }
}

$include_page = "";
if ($target_info != null)
{
    // Matched in the route array
    //
    if ($target_info['type'] ==  'redirect')
    {
        $url = $target_info['redirect'];
        foreach ($target_info['args'] as $name => $match_index)
            $url = preg_replace("!\{$name\}!", $matches[$match_index], $url);

        header('Location: '.$url, TRUE, $target_info['redir_type']);
        return;
    }

    $include_page = $target_info['include_file'];
}
else
{
    if (!preg_match("/^\w+ [\w\.\-_\/]+$/m", $request_uri))
        send_404();

    $include_page = $global_state['input']['page'];
    if (!$include_page && isset($_GET['page']) && strlen($_GET['page']))
        send_404();
    $matches = null;
}

if (!$include_page) $include_page = SITE_DEFAULT_PAGE;

# Check if page is allowed
#
if (in_array($include_page, $global_config['disabled_pages']))
    $authenticator->show_disabled();

$include_page_file = $site_views.$include_page.".inc.php";
if (!is_file($include_page_file))
    send_404();

require_once($include_page_file);

$object_name = "";
$function_name = "";

if ($target_info != null)
{
    $target = explode('.', $target_info['class']);
    if (count($target) != 2)
        die('Illegal target name.');

    $object_name = $target[0];
    $function_name = $target[1];

    for ($i = 0; $i < count($target_info['args']); $i++)
        $global_state['raw_post'][$target_info['args'][$i]] = $matches[$i + 1];
}
else
{
    $object_name = preg_replace_callback('/(?:^|[_\-\.])(.?)/',
                    function($m) {
                        return strtoupper($m[1]);
                    }, 'page_'.$include_page);
    $function_name = "html_main";
}

# Check if site logic wants global filter
#
if (function_exists('site_get_filter'))
{
    $site_filter = site_get_filter();
    array_walk($site_filter, 'validate_input');
}

function call_obj_func($global_info, $object_name, $function_name, $matches = NULL)
{
    global $authenticator;

    $include_page_filter = NULL;
    $page_permissions = NULL;
    $page_obj = NULL;

    if (!class_exists($object_name))
        http_error(500, 'Internal Server Error', "<h1>Object not found</h1>\nThe requested object could not be located. Please contact the administrator.");

    $include_page_filter = $object_name::get_filter();
    $page_permissions = $object_name::get_permissions();

    assert('is_array($include_page_filter) /* Filter does not have correct form */');

    array_walk($include_page_filter, 'validate_input');

    enforce_permissions($object_name, $page_permissions);

    if (function_exists('site_do_logic'))
        site_do_logic($global_info);

    $page_obj = new $object_name($global_info);
    $argument_count = 0;
    if (is_array($matches))
        $argument_count = count($matches) - 1;

    if ($argument_count == 0)
        $page_obj->$function_name();
    else if ($argument_count == 1)
        $page_obj->$function_name($matches[1]);
    else if ($argument_count == 2)
        $page_obj->$function_name($matches[1], $matches[2]);
    else
        echo "No method for $argument_count yet..\n";
}

call_obj_func($global_info, $object_name, $function_name, $matches);

?>
