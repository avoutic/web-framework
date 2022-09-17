<?php
namespace WebFramework\Core;

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
?>
