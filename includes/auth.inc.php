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
    abstract function deauthenticate();

    function redirect_login($type, $target)
    {
        if ($type == '401')
        {
            header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
            header("HTTP/1.0 401 Unauthorized");
            print "<h1>Page requires authentication</h1>\n";
            print "Please include a WWW-Authenticate header field in the request.\n";
        }

        else if ($type == 'redirect')
        {
            $query = $_SERVER['QUERY_STRING'];

            if (strlen($query) && substr($query, 0, 5) != 'page=')
                http_error(500, 'Internal Server Error', "<h1>Unauthorized call to authorized page</h1>\nThe call order was wrong. Please contact the administrator.");

            $pos = strpos($query, '&');
            if ($pos !== FALSE)
                $query = substr($query, $pos);
            else
                $query = "";


            header('Location: /'.$this->config['site_login_page'].'?return_page='.urlencode($target).'&return_query='.urlencode($query).'&'.add_message_to_url('info', $this->config['auth_required_message']));
        }

        else
            die('Not a known redirect type.');

        exit(0);
    }

    function show_disabled()
    {
        header('HTTP/1.0 403 Page disabled');
        print '<h1>Page has been disabled</h1>';
        print 'This page has been disabled. Please return to the main page.';
        exit(0);
    }

    function access_denied($login_page)
    {
        # Access denied
        header('HTTP/1.0 403 Access Denied');
        print '<h1>Access Denied</h1>';
        print 'You do not have the authorization to view this page. Please return to the main page or <a href="'.$login_page.'">log in</a>.';
        exit(0);
    }

    function valid_session()
    {
        $logged_in = $this->get_logged_in();
        if ($logged_in === FALSE)
            return FALSE;

        // Check for session timeout
        $current = time();
        $last_active = $current;
        if (isset($_SESSION['session_last_active']))
            $last_active = $_SESSION['session_last_active'];

        if ($current - $last_active > $this->config['session_timeout'])
        {
            $this->deauthenticate();
            set_message('info', 'Session timed out', '');
            return FALSE;
        }

        $_SESSION['session_last_active'] = $current;

        // Return valid session information
        return $logged_in;
    }
}

class AuthRedirect extends Authenticator
{
    function __construct($database, $config)
    {
        parent::__construct($database, $config);
    }

    function get_logged_in()
    {
        if (!isset($_SESSION['logged_in']))
            return FALSE;

        return $_SESSION['auth'];
    }

    function deauthenticate()
    {
        unset($_SESSION['logged_in']);
        unset($_SESSION['auth']);
        session_regenerate_id();
        session_destroy();
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

        $factory = new BaseFactory($this->global_info);

        $user = $factory->get_user_by_username($username);

        if ($user === FALSE || !$user->check_password($password))
            return FALSE;

        $info = array(
            'user_id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email,
            'permissions' => array_merge(array('logged_in'), $user->rights));

        return $info;
    }

    function deauthenticate()
    {
        # Cannot deauthenticate from server side on this authentication method
    }
};
?>
