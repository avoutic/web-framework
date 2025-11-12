<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\UserService;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Event\UserRegistered;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\UsernameUnavailableException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\RegisterService;
use WebFramework\Security\UserVerificationService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\RegisterService
 */
final class RegisterServiceTest extends Unit
{
    public function testValidatePasswordMismatch()
    {
        $instance = $this->make(
            RegisterService::class,
        );

        verify(function () use ($instance) {
            $instance->validate('username', 'email', 'password', 'verify', true);
        })
            ->callableThrows(PasswordMismatchException::class)
        ;
    }

    public function testValidateWeakPassword()
    {
        $instance = $this->make(
            RegisterService::class,
        );

        verify(function () use ($instance) {
            $instance->validate('username', 'email', 'passwor', 'passwor', true);
        })
            ->callableThrows(WeakPasswordException::class)
        ;
    }

    public function testValidateUsernameTaken()
    {
        $instance = $this->make(
            RegisterService::class,
            [
                'userService' => $this->makeEmpty(
                    UserService::class,
                    [
                        'isUsernameAvailable' => Expected::once(false),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->validate('username', 'email', 'password1', 'password1', true);
        })
            ->callableThrows(UsernameUnavailableException::class)
        ;
    }

    public function testValidateSuccess()
    {
        $instance = $this->make(
            RegisterService::class,
            [
                'userService' => $this->makeEmpty(
                    UserService::class,
                    [
                        'isUsernameAvailable' => Expected::once(true),
                    ],
                ),
            ],
        );

        verify($instance->validate('username', 'email', 'password1', 'password1', true));
    }

    public function testRegisterCreatesUserAndSendsVerification()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
        ]);

        $userService = $this->makeEmpty(
            UserService::class,
            [
                'createUser' => Expected::once($user),
            ],
        );

        $userVerificationService = $this->makeEmpty(
            UserVerificationService::class,
            [
                'sendVerifyMail' => Expected::once('test-guid-123'),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(function ($event) {
                    verify($event)->instanceOf(UserRegistered::class);
                }),
            ],
        );

        $logger = $this->makeEmpty(LoggerInterface::class);

        $instance = $this->make(
            RegisterService::class,
            [
                'userService' => $userService,
                'userVerificationService' => $userVerificationService,
                'eventService' => $eventService,
                'logger' => $logger,
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $result = $instance->register($request, 'testuser', 'test@example.com', 'password123');

        verify($result)->equals([
            'user' => $user,
            'guid' => 'test-guid-123',
        ]);
    }
}
