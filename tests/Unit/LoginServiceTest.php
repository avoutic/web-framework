<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\CaptchaRequiredException;
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
 * @coversNothing
 */
final class LoginServiceTest extends \Codeception\Test\Unit
{
    public function testValidateUnknownUser()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
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
            ->callableThrows(InvalidPasswordException::class);
    }

    public function testValidateWrongPassword()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
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
            ->callableThrows(InvalidPasswordException::class);
    }

    public function testValidateCaptchaRequired()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'userRepository' => $this->makeEmpty(
                    UserRepository::class,
                    [
                        'getUserByUsername' => $this->makeEmpty(User::class),
                    ],
                ),
                'captchaRequired' => Expected::once(true),
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify(function () use ($instance, $request) {
            $instance->validate($request, 'noname', '', false);
        })
            ->callableThrows(CaptchaRequiredException::class);
    }

    public function testValidateNotVerified()
    {
        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
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
            ->callableThrows(UserVerificationRequiredException::class);
    }

    public function testValidateSuccess()
    {
        $user = $this->makeEmpty(User::class, ['isVerified' => Expected::once(true)]);

        $instance = $this->make(
            LoginService::class,
            [
                'blacklistService' => $this->makeEmpty(NullBlacklistService::class),
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
            ],
        );

        $request = $this->makeEmpty(Request::class, ['getAttribute' => '']);

        verify($instance->validate($request, 'noname', '', true))
            ->equals($user);
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
            ],
        );

        $user = $this->makeEmpty(User::class);

        verify(function () use ($instance, $user) {
            $instance->authenticate($user, '');
        })
            ->callableThrows(InvalidPasswordException::class);
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
            ],
        );

        verify(function () use ($instance, $user) {
            $instance->authenticate($user, '');
        })
            ->callableThrows(UserVerificationRequiredException::class);
    }

    public function testAuthenticateSuccess()
    {
        $user = $this->makeEmpty(User::class, ['isVerified' => Expected::once(true)]);

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
            ],
        );

        verify($instance->authenticate($user, ''));
    }

    public function testCaptchaRequiredNoProtection()
    {
        $user = $this->makeEmpty(User::class, ['getFailedLogin' => Expected::once(200)]);

        $instance = $this->make(
            LoginService::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(false),
                    ],
                ),
            ],
        );

        verify($instance->captchaRequired($user))
            ->equals(false);
    }

    public function testCaptchaRequired()
    {
        $user = $this->makeEmpty(User::class, ['getFailedLogin' => Expected::once(200)]);

        $instance = $this->make(
            LoginService::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(true),
                    ],
                ),
            ],
        );

        verify($instance->captchaRequired($user))
            ->equals(true);
    }

    public function testCaptchaNotRequired()
    {
        $user = $this->makeEmpty(User::class, ['getFailedLogin' => Expected::once(0)]);

        $instance = $this->make(
            LoginService::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(true),
                    ],
                ),
            ],
        );

        verify($instance->captchaRequired($user))
            ->equals(false);
    }
}
