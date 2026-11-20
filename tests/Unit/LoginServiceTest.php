<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Event\EventService;
use WebFramework\Exception\AccountDisabledException;
use WebFramework\Exception\InvalidPasswordException;
use WebFramework\Exception\LoginRefusedException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\AccountStatusChecker;
use WebFramework\Security\CheckPasswordService;
use WebFramework\Security\Extension\LoginExtensionInterface;
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
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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

    public function testValidateSuccessDoesNotCheckVerification()
    {
        $user = $this->makeEmpty(User::class, [
            'isVerified' => Expected::never(),
            'isVerifiedValid' => Expected::never(),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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

    public function testAuthenticateNotVerified()
    {
        $user = $this->makeEmpty(User::class, ['isVerified' => Expected::once(false)]);

        $instance = $this->make(
            LoginService::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
                'checkPasswordService' => $this->makeEmpty(
                    CheckPasswordService::class,
                    [
                        'checkPassword' => Expected::never(),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user);
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }

    public function testAuthenticateSuccess()
    {
        $user = $this->makeEmpty(User::class, [
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(true),
            'setLastLogin' => Expected::once(),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'userRepository' => $this->makeEmpty(UserRepository::class, [
                    'save' => Expected::once(),
                ]),
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
                'checkPasswordService' => $this->makeEmpty(CheckPasswordService::class, [
                    'checkPassword' => Expected::never(),
                ]),
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
                'loginExtension' => $this->makeEmpty(
                    LoginExtensionInterface::class,
                    [
                        'preAuthenticate' => Expected::once(true),
                    ],
                ),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify($instance->authenticate($request, $user));
    }

    public function testAuthenticateRefusedByExtension()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'isVerified' => Expected::once(true),
            'isVerifiedValid' => Expected::once(true),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
                'checkPasswordService' => $this->makeEmpty(CheckPasswordService::class, [
                    'checkPassword' => Expected::never(),
                ]),
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'authenticate' => Expected::never(),
                    ],
                ),
                'eventService' => $this->makeEmpty(
                    EventService::class,
                    [
                        'dispatch' => Expected::never(),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'loginExtension' => $this->makeEmpty(
                    LoginExtensionInterface::class,
                    [
                        'preAuthenticate' => Expected::once(false),
                    ],
                ),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user);
        })
            ->callableThrows(LoginRefusedException::class)
        ;
    }

    public function testCompleteLoginAllowed()
    {
        $user = $this->makeEmpty(User::class, [
            'setLastLogin' => Expected::once(function ($timestamp) {
                verify($timestamp)->greaterThan(0);
            }),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'userRepository' => $this->makeEmpty(UserRepository::class, [
                    'save' => Expected::once(),
                ]),
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
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
                'loginExtension' => $this->makeEmpty(
                    LoginExtensionInterface::class,
                    [
                        'preAuthenticate' => Expected::once(true),
                    ],
                ),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify($instance->completeLogin($request, $user));
    }

    public function testCompleteLoginRefused()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'setLastLogin' => Expected::never(),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'userRepository' => $this->makeEmpty(UserRepository::class, [
                    'save' => Expected::never(),
                ]),
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
                'authenticationService' => $this->makeEmpty(
                    NullAuthenticationService::class,
                    [
                        'authenticate' => Expected::never(),
                    ],
                ),
                'eventService' => $this->makeEmpty(
                    EventService::class,
                    [
                        'dispatch' => Expected::never(),
                    ],
                ),
                'logger' => $this->makeEmpty(LoggerInterface::class, [
                    'debug' => Expected::once(),
                ]),
                'loginExtension' => $this->makeEmpty(
                    LoginExtensionInterface::class,
                    [
                        'preAuthenticate' => Expected::once(false),
                    ],
                ),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->completeLogin($request, $user);
        })
            ->callableThrows(LoginRefusedException::class)
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
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
                'checkPasswordService' => $this->makeEmpty(CheckPasswordService::class, [
                    'checkPassword' => Expected::never(),
                ]),
                'logger' => $this->makeEmpty(LoggerInterface::class, [
                    'debug' => Expected::once(),
                ]),
                'validityPeriodDays' => 1,
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->authenticate($request, $user);
        })
            ->callableThrows(UserVerificationRequiredException::class)
        ;
    }

    public function testValidateDisabledAccount()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'isVerified' => Expected::never(),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => false]),
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'checkPasswordService' => $this->makeEmpty(CheckPasswordService::class, [
                    'checkPassword' => Expected::once(true),
                ]),
                'userRepository' => $this->makeEmpty(UserRepository::class, [
                    'getUserByUsername' => $user,
                ]),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', 'secret');
        })
            ->callableThrows(AccountDisabledException::class)
        ;
    }

    public function testCompleteLoginDisabledAccount()
    {
        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'setLastLogin' => Expected::never(),
        ]);

        $instance = $this->make(
            LoginService::class,
            [
                'userRepository' => $this->makeEmpty(UserRepository::class, [
                    'save' => Expected::never(),
                ]),
                'accountStatusChecker' => $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => false]),
                'authenticationService' => $this->makeEmpty(NullAuthenticationService::class, [
                    'authenticate' => Expected::never(),
                ]),
                'eventService' => $this->makeEmpty(EventService::class, [
                    'dispatch' => Expected::never(),
                ]),
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'loginExtension' => $this->makeEmpty(LoginExtensionInterface::class, [
                    'preAuthenticate' => Expected::never(),
                ]),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request, $user) {
            $instance->completeLogin($request, $user);
        })
            ->callableThrows(AccountDisabledException::class)
        ;
    }
}
