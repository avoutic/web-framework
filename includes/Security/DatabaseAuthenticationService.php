<?php

namespace WebFramework\Security;

use WebFramework\Core\BrowserSessionService;
use WebFramework\Core\Database;
use WebFramework\Core\UserRightService;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

class DatabaseAuthenticationService implements AuthenticationService
{
    private ?User $user = null;

    /**
     * @param class-string<User> $userClass
     */
    public function __construct(
        private Database $database,
        private BrowserSessionService $browserSessionService,
        private UserRepository $userRepository,
        private UserRightService $userRightService,
        private int $sessionTimeout,
        private string $userClass,
    ) {
    }

    public function cleanup(): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $query = <<<'SQL'
        DELETE FROM sessions
        WHERE ADDDATE(last_active, INTERVAL ? SECOND) < ?
SQL;

        $params = [$this->sessionTimeout, $timestamp];

        $this->database->query($query, $params);
    }

    protected function registerSession(int $userId, string|false $sessionId): Session
    {
        $timestamp = date('Y-m-d H:i:s');

        return Session::create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'last_active' => $timestamp,
        ]);
    }

    public function invalidateSessions(int $userId): void
    {
        $result = $this->database->query('DELETE FROM sessions WHERE user_id = ?', [$userId]);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to delete all user\'s sessions');
        }
    }

    public function authenticate(User $user): void
    {
        // Destroy running session
        //
        $sessionId = $this->browserSessionService->get('session_id');
        if ($sessionId !== null)
        {
            $session = Session::getObjectById($sessionId);
            if ($session !== false)
            {
                $session->delete();
            }
        }

        $this->browserSessionService->regenerate();

        $session = $this->registerSession($user->getId(), $this->browserSessionService->getSessionId());

        $this->browserSessionService->set('logged_in', true);
        $this->browserSessionService->set('user_id', $user->getId());
        $this->browserSessionService->set('session_id', $session->id);
    }

    protected function isValid(): bool
    {
        $userId = $this->browserSessionService->get('user_id');
        if ($userId === null)
        {
            return false;
        }

        $sessionId = $this->browserSessionService->get('session_id');
        if ($sessionId === null)
        {
            return false;
        }

        $session = Session::getObjectById($sessionId);
        if ($session === false)
        {
            return false;
        }

        if (!$session->isValid())
        {
            $this->deauthenticate();

            return false;
        }

        return true;
    }

    public function isAuthenticated(): bool
    {
        $loggedIn = $this->browserSessionService->get('logged_in');
        if ($loggedIn !== true)
        {
            return false;
        }

        if (!$this->isValid())
        {
            return false;
        }

        return true;
    }

    public function deauthenticate(): void
    {
        $sessionId = $this->browserSessionService->get('session_id');
        if ($sessionId !== null)
        {
            $session = Session::getObjectById($sessionId);
            if ($session)
            {
                $session->delete();
            }
        }

        $this->browserSessionService->delete('logged_in');
        $this->browserSessionService->delete('session_id');

        $this->browserSessionService->destroy();
    }

    public function getAuthenticatedUser(): User
    {
        if (!$this->isAuthenticated())
        {
            throw new \RuntimeException('Not authenticated');
        }

        $userId = $this->browserSessionService->get('user_id');
        if (!is_int($userId))
        {
            throw new \RuntimeException('Browser Session invalid');
        }

        if ($this->user !== null && $this->user->getId() == $userId
            && $this->user instanceof $this->userClass)
        {
            return $this->user;
        }

        $user = $this->userRepository->getObjectById($userId);
        if ($user === null)
        {
            throw new \RuntimeException('User not present');
        }

        $this->user = $user;

        return $user;
    }

    /**
     * @param array<string> $permissions
     */
    public function userHasPermissions(array $permissions): bool
    {
        if (count($permissions) == 0)
        {
            return true;
        }

        if (!$this->isAuthenticated())
        {
            return false;
        }

        $user = $this->getAuthenticatedUser();

        foreach ($permissions as $permission)
        {
            if ($permission == 'logged_in')
            {
                continue;
            }

            try
            {
                if (!$this->userRightService->hasRight($user, $permission))
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
