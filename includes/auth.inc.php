<?php
require_once(WF::$includes.'helpers.inc.php');
require_once(WF::$includes.'base_logic.inc.php');


class Session extends DataCore
{
    static protected $table_name = 'sessions';
    static protected $base_fields = array('user_id', 'session_id', 'start', 'last_active');

    function is_valid()
    {
        // Check for session timeout
        $current = time();
        $last_active_timestamp = Helpers::mysql_datetime_to_timestamp($this->last_active);

        if ($current - $last_active_timestamp >
            $this->get_config('authenticator.session_timeout'))
        {
            return FALSE;
        }

        $timestamp = date('Y-m-d H:i:s');

        // Update timestamp every 5 minutes
        //
        if ($current - $last_active_timestamp > 60 * 5)
            $this->update_field('last_active', $timestamp);

        // Restart session every 4 hours
        //
        $start_timestamp = Helpers::mysql_datetime_to_timestamp($this->start);
        if ($current - $start_timestamp > 4 * 60 * 60)
        {
            session_regenerate_id(true);
            $this->update_field('start', $timestamp);
        }

        return TRUE;
    }
};

abstract class Authenticator extends FrameworkCore
{
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

            header('Location: '.$this->get_config('pages.login.location').'?return_page='.urlencode($target).'&return_query='.urlencode($query).'&'.$this->add_message_to_url('info', $this->get_config('authenticator.auth_required_message')), true, 302);
        }
        else if ($type == '403')
        {
            header("HTTP/1.0 403 Forbidden");
            print "<h1>Page requires authentication</h1>\n";
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
            'email' => $user->email);

        return $info;
    }
}

class AuthRedirect extends Authenticator
{
    function cleanup()
    {
        $timeout = $this->get_config('authenticator.session_timeout');
        $timestamp = date('Y-m-d H:i:s');

        $this->query('DELETE FROM sessions WHERE ADDDATE(last_active, INTERVAL ? SECOND) < ?', array($timeout, $timestamp));
    }

    function register_session($user_id, $session_id)
    {
        $timestamp = date('Y-m-d H:i:s');

        $session = Session::create(array(
                                            'user_id' => $user_id,
                                            'session_id' => $session_id,
                                            'last_active' => $timestamp));

        WF::verify($session !== FALSE, 'Failed to create Session');

        return $session;
    }

    function invalidate_sessions($user_id)
    {
        $result = $this->query('DELETE FROM sessions WHERE user_id = ?', array($user_id));
        WF::verify($result !== FALSE, 'Failed to delete all user\'s sessions');
    }

    function set_logged_in($user)
    {
        // Destroy running session
        if (isset($_SESSION['session_id']))
        {
            $session = Session::get_object_by_id($_SESSION['session_id']);
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

        $session = Session::get_object_by_id($_SESSION['session_id']);
        if ($session === FALSE)
            return FALSE;

        if (!$session->is_valid())
        {
            $this->deauthenticate();
            WF::set_message('info', 'Session timed out', '');
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
        if (isset($_SESSION['session_id']))
        {
            $session = Session::get_object_by_id($_SESSION['session_id']);
            $session->delete();
        }

        unset($_SESSION['logged_in']);
        unset($_SESSION['auth']);
        unset($_SESSION['session_id']);

        session_regenerate_id();
        session_destroy();
    }
};

class AuthWwwAuthenticate extends Authenticator
{
    protected $realm = 'Unknown realm';

    function __construct()
    {
        parent::__construct();

        if (strlen($this->get_config('realm')))
            $this->realm = $this->get_config['realm'];
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

        $factory = new BaseFactory();

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
