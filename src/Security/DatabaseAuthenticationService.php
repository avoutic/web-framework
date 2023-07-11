<?php

namespace WebFramework\Security;

use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use WebFramework\Core\Database;
use WebFramework\Core\Helpers;
use WebFramework\Entity\Session;
use WebFramework\Entity\User;
use WebFramework\Repository\SessionRepository;
use WebFramework\Repository\UserRepository;

class DatabaseAuthenticationService implements AuthenticationService
{
    private bool $sessionChecked = false;

    public function __construct(
        private Database $database,
        private SessionInterface $browserSession,
        private SessionManagerInterface $browserSessionManager,
        private SessionRepository $sessionRepository,
        private UserRepository $userRepository,
        private int $sessionTimeout,
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

    protected function registerSession(int $userId, string $sessionId): Session
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
        $this->sessionChecked = false;

        $result = $this->database->query('DELETE FROM sessions WHERE user_id = ?', [$userId]);

        if ($result === false)
        {
            throw new \RuntimeException('Failed to delete all user\'s sessions');
        }
    }

    public function authenticate(User $user): void
    {
        $this->deauthenticate();

        $session = $this->registerSession($user->getId(), $this->browserSessionManager->getId());

        $this->browserSession->set('logged_in', true);
        $this->browserSession->set('user_id', $user->getId());
        $this->browserSession->set('db_session_id', $session->getId());

        $this->sessionChecked = true;
    }

    public function validateSession(): void
    {
        if ($this->sessionChecked)
        {
            return;
        }

        // Check browser session
        //
        if (!$this->browserSession->has('logged_in')
            || !$this->browserSession->has('user_id')
            || !$this->browserSession->has('db_session_id'))
        {
            $this->browserSession->set('logged_in', false);
            $this->browserSession->set('user_id', null);
            $this->browserSession->set('db_session_id', null);

            $this->sessionChecked = true;

            return;
        }

        if ($this->browserSession->get('logged_in') !== true)
        {
            $this->sessionChecked = true;

            return;
        }

        // Retrieve and check database session
        //
        $sessionId = $this->browserSession->get('db_session_id');
        $session = $this->sessionRepository->getObjectById($sessionId);
        if ($session === null)
        {
            // If logged in but no database session, clear browser session
            //
            $this->browserSession->set('logged_in', false);
            $this->browserSession->set('user_id', null);
            $this->browserSession->set('db_session_id', null);

            $this->sessionChecked = true;

            return;
        }

        if (!$this->isDatabaseSessionValid($session))
        {
            // If logged in but database session no longer valid, clear browser session
            //
            $this->browserSession->set('logged_in', false);
            $this->browserSession->set('user_id', null);
            $this->browserSession->set('db_session_id', null);

            $this->sessionRepository->delete($session);

            $this->sessionChecked = true;

            return;
        }

        $this->sessionChecked = true;
        $this->extendDatabaseSession($session);
    }

    public function isDatabaseSessionValid(Session $session): bool
    {
        // Check for session timeout
        //
        $current = time();
        $lastActiveTimestamp = Helpers::mysqlDatetimeToTimestamp($session->getLastActive());

        return ($current - $lastActiveTimestamp <= $this->sessionTimeout);
    }

    public function extendDatabaseSession(Session $session): void
    {
        $current = time();
        $lastActiveTimestamp = Helpers::mysqlDatetimeToTimestamp($session->getLastActive());

        // Update timestamp only once every 5 minutes
        //
        if ($current - $lastActiveTimestamp < 60 * 5)
        {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $session->setLastActive($timestamp);

        // Restart session every 4 hours
        //
        $startTimestamp = Helpers::mysqlDatetimeToTimestamp($session->getStart());
        if ($current - $startTimestamp > 4 * 60 * 60)
        {
            $this->browserSessionManager->regenerateId();

            $session->setSessionId($this->browserSessionManager->getId());
            $session->setStart($timestamp);
        }

        $this->sessionRepository->save($session);
    }

    public function isAuthenticated(): bool
    {
        $this->validateSession();

        $loggedIn = $this->browserSession->get('logged_in');

        return ($loggedIn === true);
    }

    public function deauthenticate(): void
    {
        $sessionId = $this->browserSession->get('db_session_id');
        if ($sessionId !== null)
        {
            $session = $this->sessionRepository->getObjectById($sessionId);
            if ($session !== null)
            {
                $this->sessionRepository->delete($session);
            }
        }

        $this->browserSessionManager->regenerateId();

        $this->browserSession->clear();
        $this->browserSession->set('logged_in', false);
        $this->browserSession->set('user_id', null);
        $this->browserSession->set('db_session_id', null);
    }

    public function getAuthenticatedUserId(): int
    {
        if (!$this->isAuthenticated())
        {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->browserSession->get('user_id');
    }

    public function getAuthenticatedUser(): User
    {
        $userId = $this->getAuthenticatedUserId();
        $user = $this->userRepository->getObjectById($userId);
        if ($user === null)
        {
            throw new \RuntimeException('User not present');
        }

        return $user;
    }
}
