<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use Carbon\Carbon;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use WebFramework\Database\Database;
use WebFramework\Entity\Session;
use WebFramework\Entity\User;
use WebFramework\Repository\SessionRepository;
use WebFramework\Repository\UserRepository;

/**
 * Class DatabaseAuthenticationService.
 *
 * Implements the AuthenticationService interface using a database for session storage.
 */
class DatabaseAuthenticationService implements AuthenticationService
{
    private bool $sessionChecked = false;
    private ?User $authenticatedUser = null;

    /**
     * DatabaseAuthenticationService constructor.
     *
     * @param Database                $database              The database service
     * @param LoggerInterface         $logger                The logger service
     * @param SessionInterface        $browserSession        The browser session service
     * @param SessionManagerInterface $browserSessionManager The browser session manager
     * @param SessionRepository       $sessionRepository     The session repository
     * @param UserRepository          $userRepository        The user repository
     * @param int                     $sessionTimeout        The session timeout in seconds
     */
    public function __construct(
        private Database $database,
        private LoggerInterface $logger,
        private SessionInterface $browserSession,
        private SessionManagerInterface $browserSessionManager,
        private SessionRepository $sessionRepository,
        private UserRepository $userRepository,
        private int $sessionTimeout,
    ) {}

    /**
     * Perform cleanup of expired sessions.
     */
    public function cleanup(): void
    {
        $this->logger->debug('Cleaning up expired sessions');

        $timestamp = Carbon::now();

        $query = <<<'SQL'
        DELETE FROM sessions
        WHERE ADDDATE(last_active, INTERVAL ? SECOND) < ?
SQL;

        $params = [$this->sessionTimeout, $timestamp];

        $this->database->query($query, $params);
    }

    /**
     * Register a new session for a user.
     *
     * @param int    $userId    The ID of the user
     * @param string $sessionId The session ID
     *
     * @return Session The created session
     */
    private function registerSession(int $userId, string $sessionId): Session
    {
        $this->logger->debug('Registering session', ['user_id' => $userId, 'session_id' => $sessionId]);

        $timestamp = Carbon::now();

        return $this->sessionRepository->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'start' => $timestamp,
            'last_active' => $timestamp,
        ]);
    }

    /**
     * Invalidate all sessions for a user.
     *
     * @param int $userId The ID of the user
     */
    public function invalidateSessions(int $userId): void
    {
        $this->logger->debug('Invalidating sessions for user', ['user_id' => $userId]);

        $this->sessionChecked = false;

        $result = $this->database->query('DELETE FROM sessions WHERE user_id = ?', [$userId], 'Failed to delete all sessions for user');
    }

    /**
     * Authenticate a user.
     *
     * @param User $user The user to authenticate
     */
    public function authenticate(User $user): void
    {
        $this->deauthenticate();

        $this->logger->info('Authenticating user', ['user_id' => $user->getId()]);

        $session = $this->registerSession($user->getId(), $this->browserSessionManager->getId());

        $this->browserSession->set('logged_in', true);
        $this->browserSession->set('user_id', $user->getId());
        $this->browserSession->set('db_session_id', $session->getId());

        $this->authenticatedUser = $user;
        $this->sessionChecked = true;
    }

    /**
     * Validate the current session.
     */
    public function validateSession(): void
    {
        if ($this->sessionChecked)
        {
            return;
        }

        $this->logger->debug('Validating session');
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
            $this->logger->debug('No database session found, logging out');

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
            $this->logger->debug('Database session not valid, logging out');

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

    /**
     * Check if a database session is valid.
     *
     * @param Session $session The session to check
     *
     * @return bool True if the session is valid, false otherwise
     */
    public function isDatabaseSessionValid(Session $session): bool
    {
        // Check for session timeout
        //
        $lastActiveTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $session->getLastActive());
        if ($lastActiveTimestamp === null)
        {
            throw new \RuntimeException('Session last active timestamp is not parseable');
        }

        return ($lastActiveTimestamp->diffInSeconds() <= $this->sessionTimeout);
    }

    /**
     * Extend the duration of a database session.
     *
     * @param Session $session The session to extend
     */
    public function extendDatabaseSession(Session $session): void
    {
        $current = Carbon::now();
        $lastActiveTimestamp = new Carbon($session->getLastActive());

        // Update timestamp only once every 5 minutes
        //
        if ($current->diffInMinutes($lastActiveTimestamp) < 5)
        {
            return;
        }

        $this->logger->debug('Extending database session', ['session_id' => $session->getId()]);

        $timestamp = Carbon::now();
        $session->setLastActive($timestamp);

        // Restart session every 4 hours
        //
        $startTimestamp = new Carbon($session->getStart());
        if ($current->diffInHours($startTimestamp) > 4)
        {
            $this->logger->debug('Regenerating browser session ID');

            $this->browserSessionManager->regenerateId();

            $session->setSessionId($this->browserSessionManager->getId());
            $session->setStart($timestamp);
        }

        $this->sessionRepository->save($session);
    }

    /**
     * Check if a user is currently authenticated.
     *
     * @return bool True if a user is authenticated, false otherwise
     */
    public function isAuthenticated(): bool
    {
        $this->validateSession();

        $loggedIn = $this->browserSession->get('logged_in');

        return ($loggedIn === true);
    }

    /**
     * Deauthenticate the current user.
     */
    public function deauthenticate(): void
    {
        $this->logger->info('Deauthenticating user', ['user_id' => $this->browserSession->get('user_id')]);

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

        $this->authenticatedUser = null;
    }

    /**
     * Get the ID of the currently authenticated user.
     *
     * @return int The ID of the authenticated user
     *
     * @throws \RuntimeException If no user is authenticated
     */
    public function getAuthenticatedUserId(): int
    {
        if (!$this->isAuthenticated())
        {
            throw new \RuntimeException('Not authenticated');
        }

        return $this->browserSession->get('user_id');
    }

    /**
     * Get the currently authenticated user.
     *
     * @return User The authenticated user
     *
     * @throws \RuntimeException If no user is authenticated or the user cannot be found
     */
    public function getAuthenticatedUser(): User
    {
        $userId = $this->getAuthenticatedUserId();

        if ($this->authenticatedUser === null)
        {
            $this->authenticatedUser = $this->userRepository->getObjectById($userId);
            if ($this->authenticatedUser === null)
            {
                $this->logger->notice('User not present', ['user_id' => $userId]);

                throw new \RuntimeException('User not present');
            }
        }

        if ($this->authenticatedUser->getId() !== $userId)
        {
            $this->logger->notice('Authenticated user changed during run', ['user_id' => $userId]);

            throw new \RuntimeException('Authenticated user changed during run');
        }

        return $this->authenticatedUser;
    }
}
