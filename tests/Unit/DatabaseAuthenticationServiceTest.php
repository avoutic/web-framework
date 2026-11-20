<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Odan\Session\SessionInterface;
use Odan\Session\SessionManagerInterface;
use Psr\Log\LoggerInterface;
use WebFramework\Entity\Session;
use WebFramework\Repository\SessionRepository;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\DatabaseAuthenticationService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\DatabaseAuthenticationService
 */
final class DatabaseAuthenticationServiceTest extends Unit
{
    protected function _before(): void
    {
        Carbon::setTestNow('2025-01-01 12:00:00');
    }

    protected function _after(): void
    {
        Carbon::setTestNow();
    }

    /**
     * @param array<string, mixed> $sessionRepositoryStubs
     * @param array<string, mixed> $sessionManagerStubs
     */
    private function makeInstance(array $sessionRepositoryStubs, array $sessionManagerStubs = []): DatabaseAuthenticationService
    {
        return new DatabaseAuthenticationService(
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(SessionInterface::class),
            $this->makeEmpty(SessionManagerInterface::class, $sessionManagerStubs),
            $this->makeEmpty(SessionRepository::class, $sessionRepositoryStubs),
            $this->makeEmpty(UserRepository::class),
            3600,
        );
    }

    private function makeSession(string $start, string $lastActive): Session
    {
        return $this->make(
            Session::class,
            [
                'id' => 1,
                'userId' => 1,
                'sessionId' => 'old-session-id',
                'start' => $start,
                'lastActive' => $lastActive,
            ],
        );
    }

    public function testExtendWithinFiveMinutesDoesNotWrite()
    {
        $instance = $this->makeInstance(
            ['save' => Expected::never()],
            ['regenerateId' => Expected::never()],
        );

        $session = $this->makeSession('2025-01-01 11:50:00', '2025-01-01 11:56:00');

        $instance->extendDatabaseSession($session);

        verify($session->getLastActive())->equals('2025-01-01 11:56:00');
    }

    public function testExtendAfterFiveMinutesUpdatesLastActive()
    {
        $instance = $this->makeInstance(
            ['save' => Expected::once()],
            ['regenerateId' => Expected::never()],
        );

        $session = $this->makeSession('2025-01-01 11:50:00', '2025-01-01 11:54:00');

        $instance->extendDatabaseSession($session);

        verify($session->getLastActive())->equals('2025-01-01 12:00:00');
        verify($session->getStart())->equals('2025-01-01 11:50:00');
        verify($session->getSessionId())->equals('old-session-id');
    }

    public function testExtendWithinFourHoursKeepsSessionId()
    {
        $instance = $this->makeInstance(
            ['save' => Expected::once()],
            ['regenerateId' => Expected::never()],
        );

        $session = $this->makeSession('2025-01-01 08:30:00', '2025-01-01 11:30:00');

        $instance->extendDatabaseSession($session);

        verify($session->getSessionId())->equals('old-session-id');
        verify($session->getStart())->equals('2025-01-01 08:30:00');
    }

    public function testExtendAfterFourHoursRegeneratesSessionId()
    {
        $instance = $this->makeInstance(
            ['save' => Expected::once()],
            [
                'regenerateId' => Expected::once(),
                'getId' => 'new-session-id',
            ],
        );

        $session = $this->makeSession('2025-01-01 07:30:00', '2025-01-01 11:30:00');

        $instance->extendDatabaseSession($session);

        verify($session->getSessionId())->equals('new-session-id');
        verify($session->getStart())->equals('2025-01-01 12:00:00');
        verify($session->getLastActive())->equals('2025-01-01 12:00:00');
    }

    public function testSessionValidWithinTimeout()
    {
        $instance = $this->makeInstance([]);

        $session = $this->makeSession('2025-01-01 10:00:00', '2025-01-01 11:00:00');

        verify($instance->isDatabaseSessionValid($session))->equals(true);
    }

    public function testSessionInvalidAfterTimeout()
    {
        $instance = $this->makeInstance([]);

        $session = $this->makeSession('2025-01-01 10:00:00', '2025-01-01 10:59:59');

        verify($instance->isDatabaseSessionValid($session))->equals(false);
    }
}
