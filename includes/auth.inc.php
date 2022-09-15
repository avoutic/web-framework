<?php
namespace WebFramework\Core;

/**
 * @property array<string> $base_fields
 */
class Session extends DataCore
{
    static protected string $table_name = 'sessions';
    static protected array $base_fields = array('user_id', 'session_id', 'start', 'last_active');

    public string $user_id;
    public string $session_id;
    public string $start;
    public string $last_active;

    public function is_valid(): bool
    {
        // Check for session timeout
        $current = time();
        $last_active_timestamp = Helpers::mysql_datetime_to_timestamp($this->last_active);

        if ($current - $last_active_timestamp >
            $this->get_config('authenticator.session_timeout'))
        {
            return false;
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

        return true;
    }
};

abstract class Authenticator extends FrameworkCore
{
    abstract public function set_logged_in(User $user): void;

    /**
     * @return bool|array<mixed>
    */
    abstract public function get_logged_in(): bool|array;
    abstract public function logoff(): void;
    abstract public function cleanup(): void;
    abstract public function auth_invalidate_sessions(int $user_id): void;

    protected string $realm;

    public function redirect_login(string $type, string $target): void
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
            $query = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';

            header('Location: '.$this->get_config('base_url').$this->get_config('actions.login.location').'?return_page='.urlencode($target).'&return_query='.urlencode($query).'&'.$this->get_message_for_url('info', $this->get_config('authenticator.auth_required_message')), true, 302);
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


    // Deprecated (Remove for v4)
    //
    public function show_disabled(): void
    {
        trigger_error("Authenticator->show_disabled()", E_USER_DEPRECATED);

        header('HTTP/1.0 403 Page disabled');
        print '<h1>Page has been disabled</h1>';
        print 'This page has been disabled. Please return to the main page.';
        exit(0);
    }

    // Deprecated (Remove for v4)
    //
    public function access_denied(string $login_page): void
    {
        trigger_error("Authenticator->access_denied()", E_USER_DEPRECATED);

        # Access denied
        header('HTTP/1.0 403 Access Denied');
        print '<h1>Access Denied</h1>';
        print 'You do not have the authorization to view this page. Please return to the main page or <a href="'.$login_page.'">log in</a>.';
        exit(0);
    }

    /**
     * @return array{user: User, user_id: int, username: string, email: string}
     */
    public function get_auth_array(User $user): array
    {
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
    public function cleanup(): void
    {
        if (isset($_SESSION['last_session_cleanup']) &&
            $_SESSION['last_session_cleanup'] + 60 * 60 > time())
        {
            // Skip, because already cleaned in the last hour
            return;
        }

        $timeout = $this->get_config('authenticator.session_timeout');
        $timestamp = date('Y-m-d H:i:s');

        $this->query('DELETE FROM sessions WHERE ADDDATE(last_active, INTERVAL ? SECOND) < ?', array($timeout, $timestamp));

        $_SESSION['last_session_cleanup'] = time();
    }

    protected function register_session(int $user_id, string|false $session_id): Session
    {
        $timestamp = date('Y-m-d H:i:s');

        $session = Session::create(array(
            'user_id' => $user_id,
            'session_id' => $session_id,
            'last_active' => $timestamp,
        ));

        $this->verify($session !== false, 'Failed to create Session');

        return $session;
    }

    public function auth_invalidate_sessions(int $user_id): void
    {
        $result = $this->query('DELETE FROM sessions WHERE user_id = ?', array($user_id));
        $this->verify($result !== false, 'Failed to delete all user\'s sessions');
    }

    public function set_logged_in(User $user): void
    {
        // Destroy running session
        if (isset($_SESSION['session_id']))
        {
            $session = Session::get_object_by_id($_SESSION['session_id']);
            if ($session !== false)
                $session->delete();
        }

        session_regenerate_id(true);

        $_SESSION['logged_in'] = true;
        $_SESSION['auth'] = $this->get_auth_array($user);
        $session = $this->register_session($user->id, session_id());
        $_SESSION['session_id'] = $session->id;
    }

    protected function is_valid(): bool
    {
        if (!isset($_SESSION['session_id']))
            return false;

        $session = Session::get_object_by_id($_SESSION['session_id']);
        if ($session === false)
            return false;

        if (!$session->is_valid())
        {
            $this->logoff();
            $this->add_message('info', 'Session timed out', '');
            return false;
        }

        return true;
    }

    /**
     * @return array<mixed>|false
     */
    public function get_logged_in(): array|false
    {
        if (!isset($_SESSION['logged_in']))
            return false;

        if (!$this->is_valid())
            return false;

        return $_SESSION['auth'];
    }

    public function logoff(): void
    {
        if (isset($_SESSION['session_id']))
        {
            $session = Session::get_object_by_id($_SESSION['session_id']);
            if ($session)
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
    function __construct()
    {
        parent::__construct();

        if (strlen($this->get_config('realm')))
            $this->realm = $this->get_config('realm');
        else
            $this->realm = 'Unknown realm';
    }

    public function cleanup(): void
    {
        # Nothing to cleanup
    }

    public function set_logged_in(User $user): void
    {
        # Cannot specifically log in on this authentication method
        # Done every get_logged_in() call
    }

    /**
     * @return false|array{user: User, user_id: int, username: string, email: string}
     */
    public function get_logged_in(): false|array
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            !isset($_SERVER['PHP_AUTH_PW']))
        {
            return false;
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = sha1($_SERVER['PHP_AUTH_PW']);

        $factory = new BaseFactory();

        $user = $factory->get_user_by_username($username);

        if ($user === false || !$user->check_password($password))
            return false;

        $info = $this->get_auth_array($user);

        return $info;
    }

    public function logoff(): void
    {
        # Cannot deauthenticate from server side on this authentication method
    }

    public function auth_invalidate_sessions(int $user_id): void
    {
        # Cannot deauthenticate from server side on this authentication method
    }
};
?>
