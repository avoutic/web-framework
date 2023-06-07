<?php

namespace WebFramework\Security;

use WebFramework\Core\BrowserSessionService;
use WebFramework\Core\ConfigService;
use WebFramework\Core\Database;
use WebFramework\Core\Helpers;
use WebFramework\Core\UserRightService;
use WebFramework\Entity\Session;
use WebFramework\Entity\User;
use WebFramework\Repository\SessionRepository;
use WebFramework\Repository\UserRepository;

class DatabaseAuthenticationService implements AuthenticationService
{
    private ?User $user = null;
    private bool $sessionValid = false;

    /**
     * @param class-string<User> $userClass
     */
    public function __construct(
        private Database $database,
        private BrowserSessionService $browserSessionService,
        private ConfigService $configService,
        private SessionRepository $sessionRepository,
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

        return $this->sessionRepository->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'start' => $timestamp,
            'last_active' => $timestamp,
        ]);
    }

    public function invalidateSessions(int $userId): void
    {
        $this->sessionValid = false;

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
            $session = $this->sessionRepository->getObjectById($sessionId);
            if ($session !== null)
            {
                $this->sessionRepository->delete($session);
            }
        }

        $this->browserSessionService->regenerate();

        $session = $this->registerSession($user->getId(), $this->browserSessionService->getSessionId());

        $this->browserSessionService->set('logged_in', true);
        $this->browserSessionService->set('user_id', $user->getId());
        $this->browserSessionService->set('session_id', $session->getId());
    }

    protected function isValid(): bool
    {
        if ($this->sessionValid)
        {
            return true;
        }

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

        $session = $this->sessionRepository->getObjectById($sessionId);
        if ($session === null)
        {
            return false;
        }

        if (!$this->isSessionValid($session))
        {
            $this->deauthenticate();

            return false;
        }

        $this->sessionValid = true;

        return true;
    }

    public function isSessionValid(Session $session): bool
    {
        // Check for session timeout
        $current = time();
        $lastActiveTimestamp = Helpers::mysqlDatetimeToTimestamp($session->getLastActive());

        if ($current - $lastActiveTimestamp >
            $this->configService->get('authenticator.session_timeout'))
        {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');

        // Update timestamp every 5 minutes
        //
        if ($current - $lastActiveTimestamp > 60 * 5)
        {
            $session->setLastActive($timestamp);
        }

        // Restart session every 4 hours
        //
        $startTimestamp = Helpers::mysqlDatetimeToTimestamp($session->getStart());
        if ($current - $startTimestamp > 4 * 60 * 60)
        {
            session_regenerate_id(true);
            $session->setStart($timestamp);
        }

        $this->sessionRepository->save($session);

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
        $this->sessionValid = false;

        $sessionId = $this->browserSessionService->get('session_id');
        if ($sessionId !== null)
        {
            $session = $this->sessionRepository->getObjectById($sessionId);
            if ($session !== null)
            {
                $this->sessionRepository->delete($session);
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
