<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\CheckPasswordService;
use WebFramework\Security\LoginService;
use WebFramework\Security\NullAuthenticationService;
use WebFramework\Security\NullBlacklistService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\LoginService
 */
final class LoginServiceTest extends Unit
{
    public function testValidateUnknownUser()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => null,
                    ],
                ),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', '', false);
        })
            ->callableThrows(InvalidPasswordException::class)
        ;
    }

    public function testValidateWrongPassword()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(false),
                    ],
                ),
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => $this->makeEmpty(User::class),
                    ],
                ),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', '', true);
        })
            ->callableThrows(InvalidPasswordException::class)
        ;
    }

    public function testValidateNotVerified()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => $this->makeEmpty(
                            User::class,
                            [
                                'isVerified' => Expected::once(false),
                            ],
                        ),
                    ],
                ),
                'captchaRequired' => Expected::never(),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', '', true);
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }

    public function testValidateSuccess()
    {
        $user = $this->makeEmpty(User::class, [
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(true),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => $user,
                    ],
                ),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify($instance->validate($request, 'noname', '', true))
            ->equals($user)
        ;
    }

    public function testAuthenticateWrongPassword()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(false),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);
        $user = $this->makeEmpty(User::class);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user, '');
        })
            ->callableThrows(InvalidPasswordException::class)
        ;
    }

    public function testAuthenticateNotVerified()
    {
        $user = $this->makeEmpty(User::class, ['isVerified' => Expected::once(false)]);

        $instance = $this->make(
            LoginService::class,
            [
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user, '');
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }

    public function testAuthenticateSuccess()
    {
        $user = $this->makeEmpty(User::class, [
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(true),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'authenticate' => Expected::once(),
                    ],
                ),
                'eventService' => $this->makeEmpty(
                    EventService::class,
                    [
                        'dispatch' => Expected::once(),
                    ],
                ),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify($instance->authenticate($request, $user, ''));
    }

    public function testValidateVerificationExpired()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => Expected::once(1),
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(false),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class, [
                    'debug' => Expected::once(),
                ]),
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => $user,
                    ],
                ),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', '', true);
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }

    public function testAuthenticateVerificationExpired()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => Expected::once(1),
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(false),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::once(true),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class, [
                    'debug' => Expected::once(),
                ]),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user, '');
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }
}
