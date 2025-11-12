<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Core\UserService;
use WebFramework\Exception\PasswordMismatchException;
use WebFramework\Exception\UsernameUnavailableException;
use WebFramework\Exception\WeakPasswordException;
use WebFramework\Security\RegisterService;

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
}
