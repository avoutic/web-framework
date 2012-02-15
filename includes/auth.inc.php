<?php
abstract class Authenticator
{
    protected $config;
    protected $database;

    function __construct($database, $config)
    {
        $this->database = $database;
        $this->config = $config;
    }

    abstract function get_logged_in();
    abstract function redirect_login($target);

    function show_disabled()
    {
        header("HTTP/1.0 403 Page disabled");
        print "<h1>Page has been disabled</h1>\n";
        print "This page has been disabled. Please return to the main page.";
        exit(0);
    }

    function access_denied()
    {
        # Access denied
        header("HTTP/1.0 403 Access Denied");
        print "<h1>Access Denied</h1>\n";
        print "You do not have the authorization to view this page. Please return to the main page.";
        exit(0);
    }
}

class AuthForm extends Authenticator
{
    function __construct($database, $config)
    {
        parent::__construct($database, $config);
    }

    function get_logged_in()
    {
        if (!isset($_SESSION['logged_in']))
            return FALSE;

        $info = array(
            'user_id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'name' => $_SESSION['name'],
            'email' => $_SESSION['email'],
            'permissions' => $_SESSION['permissions']);

        if (isset($_SESSION['first_name']))
            $info['first_name'] = $_SESSION['first_name'];
        if (isset($_SESSION['last_name']))
            $info['last_name'] = $_SESSION['last_name'];

        return $info;
    }

    function redirect_login($target)
    {
        $query = $_SERVER['QUERY_STRING'];

        if (substr($query, 0, 5) != 'page=')
            http_error(500, 'Internal Server Error', '<h1>Unauthorized call to authorized page</h1>\nThe call order was wrong. Please contact the administrator.');

        $pos = strpos($query, '&');
        if ($pos !== FALSE)
            $query = substr($query, $pos);
        else
            $query = "";

        header('Location: /'.$this->config['site_login_page'].'?mtype=info&message='.urlencode($config['auth_required_page']).'&return_page='.urlencode($target).'&return_query='.urlencode($query));
        exit(0);
    }
};

require_once($includes."base_logic.inc.php");

class AuthWwwAuthenticate extends Authenticator
{
    protected $realm = 'Unknown realm';

    function __construct($database, $config)
    {
        parent::__construct($database, $config);

        if (isset($config['realm']))
            $this->realm = $config['realm'];
    }

    function get_logged_in()
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            !isset($_SERVER['PHP_AUTH_PW']))
        {
            return FALSE;
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = sha1($_SERVER['PHP_AUTH_PW']);

        $factory = new BaseFactory($database);

        $user = $factory->get_user_by_username($username, 'UserFull');

        if ($user === FALSE || !$user->check_password($password))
            return FALSE;

        $info = array(
            'user_id' => $user->get_id(),
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'permissions' => array_merge(array('logged_in'), $user->permissions));

        return $info;
    }

    function redirect_login($target)
    {
        header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
        header("HTTP/1.0 401 Unauthorized");
        print "<h1>Page requires authentication</h1>\n";
        print "Please include a WWW-Authenticate header field in the request.\n";
    }
};
?>
