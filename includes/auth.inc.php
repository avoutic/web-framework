<?php
require_once($includes.'helpers.inc.php');

class Session extends DataCore
{
    static protected $table_name = 'sessions';
    static protected $base_fields = array('user_id', 'session_id', 'last_active');

    function is_valid()
    {
        // Check for session timeout
        $current = time();
        $last_active_timestamp = Helpers::mysql_datetime_to_timestamp($this->last_active);

        if ($current - $last_active_timestamp >
            $this->global_info['config']['authenticator']['session_timeout'])
        {
            return FALSE;
        }

        $timestamp = date('Y-m-d H:i:s');

        $this->update_field('last_active', $timestamp);

        return TRUE;
    }
};

abstract class Authenticator
{
    protected $global_info;
    protected $config;

    function __construct($global_info)
    {
        $this->global_info = $global_info;
        $this->config = $global_info['config']['authenticator'];
    }

    abstract function set_logged_in($user);
    abstract function get_logged_in();
    abstract function deauthenticate();
    abstract function cleanup();

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

    function get_auth_array($user)
    {
        if ($user === FALSE)
            return FALSE;

        $info = array(
            'user' => $user,
            'user_id' => $user->id,
            'username' => $user->username,
            'name' => $user->name,
            'email' => $user->email);

        return $info;
    }
}

class AuthRedirect extends Authenticator
{
    function __construct($global_info)
    {
        parent::__construct($global_info);
    }

    function cleanup()
    {
        $timeout = $this->global_info['config']['authenticator']['session_timeout'];

        $this->global_info['database']->Query('DELETE FROM sessions WHERE ADDDATE(last_active, INTERVAL ? SECOND) < UTC_TIMESTAMP()', array($timeout));
    }

    function register_session($user_id, $session_id)
    {
        $timestamp = date('Y-m-d H:i:s');

        $session = Session::create($this->global_info, array(
                                            'user_id' => $user_id,
                                            'session_id' => $session_id,
                                            'last_active' => $timestamp));

        assert('$session !== FALSE /* Failed to create Session */');

        return $session;
    }

    function invalidate_sessions($user_id)
    {
        $result = $this->global_info['database']->Query('DELETE FROM sessions WHERE user_id = ?', array($user_id));
        assert('$result !== FALSE /* Failed to delete all user sessions */');
    }

    function set_logged_in($user)
    {
        // Destroy running session
        if (isset($_SESSION['session_id']))
        {
            $session = Session::get_object_by_id($this->global_info, $_SESSION['session_id']);
            if ($session !== FALSE)
                $session->delete();
        }

        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['auth'] = $this->get_auth_array($user);
        $session = $this->register_session($user->id, session_id());
        $_SESSION['session_id'] = $session->id;
    }

    function is_valid()
    {
        if (!isset($_SESSION['session_id']))
            return FALSE;

        $session = Session::get_object_by_id($this->global_info, $_SESSION['session_id']);
        if ($session === FALSE)
            return FALSE;

        if (!$session->is_valid())
        {
            $this->deauthenticate();
            set_message('info', 'Session timed out', '');
            return FALSE;
        }

        return TRUE;
    }

    function get_logged_in()
    {
        if (!isset($_SESSION['logged_in']))
            return FALSE;

        if (!$this->is_valid())
            return FALSE;

        return $_SESSION['auth'];
    }

    function deauthenticate()
    {
        $session = Session::get_object_by_id($this->global_info, $_SESSION['session_id']);
        $session->delete();

        unset($_SESSION['logged_in']);
        unset($_SESSION['auth']);
        unset($_SESSION['session_id']);

        session_regenerate_id();
        session_destroy();
    }
};

require_once($includes."base_logic.inc.php");

class AuthWwwAuthenticate extends Authenticator
{
    protected $realm = 'Unknown realm';

    function __construct($global_info)
    {
        parent::__construct($global_info);

        if (isset($this->config['realm']))
            $this->realm = $this->config['realm'];
    }

    function cleanup()
    {
        # Nothing to cleanup
    }

    function set_logged_in($user)
    {
        # Cannot specifically log in on this authentication method
        # Done every get_logged_in() call
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

        $info = $this->get_auth_array($user);

        return $info;
    }

    function deauthenticate()
    {
        # Cannot deauthenticate from server side on this authentication method
    }
};
?>
