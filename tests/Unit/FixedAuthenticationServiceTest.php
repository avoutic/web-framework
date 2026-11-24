<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Entity\User;
use WebFramework\Security\AuthenticationService;
use WebFramework\Testing\FixedAuthenticationService;

/**
 * @internal
 *
 * @covers \WebFramework\Testing\FixedAuthenticationService
 */
final class FixedAuthenticationServiceTest extends Unit
{
    private function buildUser(int $id): User
    {
        return $this->makeEmpty(User::class, ['getId' => $id]);
    }

    public function testNobodyIsLoggedInByDefault()
    {
        $service = new FixedAuthenticationService();

        verify($service)->instanceOf(AuthenticationService::class);
        verify($service->isAuthenticated())->false();

        verify(function () use ($service) {
            $service->getAuthenticatedUser();
        })->callableThrows(\RuntimeException::class, 'Not authenticated');

        verify(function () use ($service) {
            $service->getAuthenticatedUserId();
        })->callableThrows(\RuntimeException::class, 'Not authenticated');
    }

    public function testLoggedInAsTheGivenUser()
    {
        $user = $this->buildUser(7);
        $service = new FixedAuthenticationService($user);

        verify($service->isAuthenticated())->true();
        verify($service->getAuthenticatedUser())->same($user);
        verify($service->getAuthenticatedUserId())->equals(7);
    }

    public function testAuthenticateAndDeauthenticate()
    {
        $service = new FixedAuthenticationService();
        $user = $this->buildUser(7);

        $service->authenticate($user);
        verify($service->getAuthenticatedUser())->same($user);

        $service->deauthenticate();
        verify($service->isAuthenticated())->false();
    }

    public function testInvalidatingTheSessionsOfTheUserLogsOut()
    {
        $service = new FixedAuthenticationService($this->buildUser(7));

        $service->invalidateSessions(8);
        verify($service->isAuthenticated())->true();

        $service->invalidateSessions(7);
        verify($service->isAuthenticated())->false();
    }
}
