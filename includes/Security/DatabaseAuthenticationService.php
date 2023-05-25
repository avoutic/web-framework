<?php

namespace WebFramework\Security;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\BrowserSessionService;
use WebFramework\Core\Database;
use WebFramework\Core\User;

class DatabaseAuthenticationService implements AuthenticationService
{
    private ?User $user = null;

    /**
     * @param class-string<User> $user_class
     */
    public function __construct(
        private Database $database,
        private BrowserSessionService $browser_session_service,
        private BaseFactory $user_factory,
        private int $session_timeout,
        private string $user_class,
    ) {
    }

    public function cleanup(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $query = <<<'SQL'
        DELETE FROM sessions
        WHERE ADDDATE(last_active, INTERVAL ? SECOND) < ?
SQL;

        $params = [$this->session_timeout, $timestamp];

        $this->database->query($query, $params);
    }

    protected function register_session(int $user_id, string|false $session_id): Session
    {
        $timestamp = date('Y-m-d H:i:s');

        return Session::create([
            'user_id' => $user_id,
            'session_id' => $session_id,
            'last_active' => $timestamp,
        ]);
    }

    public function invalidate_sessions(int $user_id): void
    {
        $result = $this->database->query('DELETE FROM sessions WHERE user_id = ?', [$user_id]);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to delete all user\'s sessions');
        }
    }

    public function authenticate(User $user): void
    {
        // Destroy running session
        //
        $session_id = $this->browser_session_service->get('session_id');
        if ($session_id !== null)
        {
            $session = Session::get_object_by_id($session_id);
            if ($session !== false)
            {
                $session->delete();
            }
        }

        $this->browser_session_service->regenerate();

        $session = $this->register_session($user->id, $this->browser_session_service->get_session_id());

        $this->browser_session_service->set('logged_in', true);
        $this->browser_session_service->set('user_id', $user->id);
        $this->browser_session_service->set('session_id', $session->id);
    }

    protected function is_valid(): bool
    {
        $user_id = $this->browser_session_service->get('user_id');
        if ($user_id === null)
        {
            return false;
        }

        $session_id = $this->browser_session_service->get('session_id');
        if ($session_id === null)
        {
            return false;
        }

        $session = Session::get_object_by_id($session_id);
        if ($session === false)
        {
            return false;
        }

        if (!$session->is_valid())
        {
            $this->deauthenticate();

            return false;
        }

        return true;
    }

    public function is_authenticated(): bool
    {
        $logged_in = $this->browser_session_service->get('logged_in');
        if ($logged_in !== true)
        {
            return false;
        }

        if (!$this->is_valid())
        {
            return false;
        }

        return true;
    }

    public function deauthenticate(): void
    {
        $session_id = $this->browser_session_service->get('session_id');
        if ($session_id !== null)
        {
            $session = Session::get_object_by_id($session_id);
            if ($session)
            {
                $session->delete();
            }
        }

        $this->browser_session_service->delete('logged_in');
        $this->browser_session_service->delete('session_id');

        $this->browser_session_service->destroy();
    }

    public function get_authenticated_user(): User
    {
        if (!$this->is_authenticated())
        {
            throw new \RuntimeException('Not authenticated');
        }

        $user_id = $this->browser_session_service->get('user_id');
        if (!is_int($user_id))
        {
            throw new \RuntimeException('Browser Session invalid');
        }

        if ($this->user !== null && $this->user->id == $user_id
            && $this->user instanceof $this->user_class)
        {
            return $this->user;
        }

        $user = $this->user_factory->get_user($user_id, $this->user_class);
        if ($user === false)
        {
            throw new \RuntimeException('User not present');
        }

        $this->user = $user;

        return $user;
    }

    /**
     * @param array<string> $permissions
     */
    public function user_has_permissions(array $permissions): bool
    {
        if (count($permissions) == 0)
        {
            return true;
        }

        if (!$this->is_authenticated())
        {
            return false;
        }

        $user = $this->get_authenticated_user();

        foreach ($permissions as $permission)
        {
            if ($permission == 'logged_in')
            {
                continue;
            }

            try
            {
                if (!$user->has_right($permission))
                {
                    return false;
                }
            }
            catch (\Throwable $e)
            {
                // In case the user object changed (in name / namespace) an exception for
                // deserialization will be thrown. Deauthenticate instead.
                //
                $this->deauthenticate();

                return false;
            }
        }

        return true;
    }
}
