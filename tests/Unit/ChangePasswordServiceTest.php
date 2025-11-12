<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserPasswordChanged;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangePasswordService;
use WebFramework\Security\PasswordHashService;
use WebFramework\Security\SecurityIteratorService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\ChangePasswordService
 */
final class ChangePasswordServiceTest extends Unit
{
    public function testValidateSuccess()
    {
        $user = $this->make(User::class, [
            'solidPassword' => 'sha256:1000:salt123:hash123',
        ]);

        $passwordHashService = $this->makeEmpty(
            PasswordHashService::class,
            [
                'checkPassword' => Expected::once(true),
            ],
        );

        $service = $this->make(
            ChangePasswordService::class,
            [
                'passwordHashService' => $passwordHashService,
            ],
        );

        $service->validate($user, 'oldpassword', 'newpassword123', 'newpassword123');
    }

    public function testValidateThrowsExceptionWhenPasswordsMismatch()
    {
        $user = $this->makeEmpty(User::class);

        $service = $this->make(ChangePasswordService::class);

        verify(function () use ($service, $user) {
            $service->validate($user, 'oldpassword', 'newpassword123', 'differentpassword');
        })->callableThrows(PasswordMismatchException::class);
    }

    public function testValidateThrowsExceptionWhenPasswordWeak()
    {
        $user = $this->makeEmpty(User::class);

        $service = $this->make(ChangePasswordService::class);

        verify(function () use ($service, $user) {
            $service->validate($user, 'oldpassword', 'short', 'short');
        })->callableThrows(WeakPasswordException::class);
    }

    public function testValidateThrowsExceptionWhenOldPasswordIncorrect()
    {
        $user = $this->make(User::class, [
            'solidPassword' => 'sha256:1000:salt123:hash123',
        ]);

        $passwordHashService = $this->makeEmpty(
            PasswordHashService::class,
            [
                'checkPassword' => Expected::once(false),
            ],
        );

        $service = $this->make(
            ChangePasswordService::class,
            [
                'passwordHashService' => $passwordHashService,
            ],
        );

        verify(function () use ($service, $user) {
            $service->validate($user, 'wrongpassword', 'newpassword123', 'newpassword123');
        })->callableThrows(InvalidPasswordException::class);
    }

    public function testChangePasswordSuccess()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'solidPassword' => 'sha256:1000:salt123:hash123',
        ]);

        $passwordHashService = $this->makeEmpty(
            PasswordHashService::class,
            [
                'checkPassword' => Expected::once(true),
                'generateHash' => Expected::once('sha256:1000:newsalt:newhash'),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'save' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(function ($event) {
                    verify($event)->instanceOf(UserPasswordChanged::class);
                }),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'incrementFor' => Expected::once(1),
            ],
        );

        $authenticationService = $this->makeEmpty(
            AuthenticationService::class,
            [
                'invalidateSessions' => Expected::once(),
                'authenticate' => Expected::once(),
            ],
        );

        $service = $this->make(
            ChangePasswordService::class,
            [
                'passwordHashService' => $passwordHashService,
                'userRepository' => $userRepository,
                'eventService' => $eventService,
                'securityIteratorService' => $securityIteratorService,
                'authenticationService' => $authenticationService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->changePassword($request, $user, 'oldpassword', 'newpassword123');

        verify($user->getSolidPassword())->equals('sha256:1000:newsalt:newhash');
    }

    public function testChangePasswordThrowsExceptionWhenPasswordWeak()
    {
        $user = $this->makeEmpty(User::class);

        $service = $this->make(ChangePasswordService::class);

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->changePassword($request, $user, 'oldpassword', 'short');
        })->callableThrows(WeakPasswordException::class);
    }

    public function testChangePasswordThrowsExceptionWhenOldPasswordIncorrect()
    {
        $user = $this->make(User::class, [
            'id' => 1,
            'solidPassword' => 'sha256:1000:salt123:hash123',
        ]);

        $passwordHashService = $this->makeEmpty(
            PasswordHashService::class,
            [
                'checkPassword' => Expected::once(false),
            ],
        );

        $service = $this->make(
            ChangePasswordService::class,
            [
                'passwordHashService' => $passwordHashService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->changePassword($request, $user, 'wrongpassword', 'newpassword123');
        })->callableThrows(InvalidPasswordException::class);
    }
}
