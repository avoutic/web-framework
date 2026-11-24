<?php

namespace Tests\Unit\Actions;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Actions\Login;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\AccountDisabledException;
use WebFramework\Exception\LoginRefusedException;
use WebFramework\Exception\UserVerificationRequiredException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Security\Extension\LoginExtensionInterface;
use WebFramework\Security\LoginService;
use WebFramework\Security\NullAuthenticationService;
use WebFramework\Security\UserVerificationService;
use WebFramework\Security\VerifyLocation;
use WebFramework\Validation\InputValidationService;

/**
 * @internal
 *
 * @covers \WebFramework\Actions\Login
 */
final class LoginTest extends Unit
{
    public function testRefusedLoginRendersFormWithError()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, [
            'getAttribute' => true,
            'getParam' => '',
            'getParams' => [],
        ]);

        $instance = new Login(
            authenticationService: $this->makeEmpty(NullAuthenticationService::class, ['isAuthenticated' => false]),
            configService: $this->makeEmpty(ConfigService::class, ['get' => 'email']),
            inputValidationService: $this->makeEmpty(InputValidationService::class, [
                'validate' => ['username' => 'user@example.com', 'password' => 'secret'],
            ]),
            loginExtension: $this->makeEmpty(LoginExtensionInterface::class, [
                'getCustomParams' => [],
                'preValidate' => true,
                'customValueCheck' => true,
            ]),
            loginService: $this->makeEmpty(LoginService::class, [
                'validate' => $user,
                'authenticate' => Expected::once(function () use ($user) {
                    throw new LoginRefusedException($user);
                }),
            ]),
            messageService: $this->makeEmpty(MessageService::class, [
                'add' => Expected::once(function ($type, $message) {
                    verify($type)->equals('error');
                    verify($message)->equals('login.refused');
                }),
            ]),
            renderer: $this->makeEmpty(RenderService::class, [
                'render' => Expected::once($response),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildQueryRedirect' => Expected::never(),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class),
            verifyLocation: $this->makeEmpty(VerifyLocation::class, ['getFor' => '/verify']),
            templateName: 'Login.latte',
        );

        verify($instance($request, $response, []))->same($response);
    }

    public function testDisabledAccountRendersFormWithError()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, [
            'getAttribute' => true,
            'getParam' => '',
            'getParams' => [],
        ]);

        $instance = new Login(
            authenticationService: $this->makeEmpty(NullAuthenticationService::class, ['isAuthenticated' => false]),
            configService: $this->makeEmpty(ConfigService::class, ['get' => 'email']),
            inputValidationService: $this->makeEmpty(InputValidationService::class, [
                'validate' => ['username' => 'user@example.com', 'password' => 'secret'],
            ]),
            loginExtension: $this->makeEmpty(LoginExtensionInterface::class, [
                'getCustomParams' => [],
                'preValidate' => true,
                'customValueCheck' => true,
            ]),
            loginService: $this->makeEmpty(LoginService::class, [
                'validate' => $user,
                'authenticate' => Expected::once(function () use ($user) {
                    throw new AccountDisabledException($user);
                }),
            ]),
            messageService: $this->makeEmpty(MessageService::class, [
                'add' => Expected::once(function ($type, $message) {
                    verify($type)->equals('error');
                    verify($message)->equals('login.account_disabled');
                }),
            ]),
            renderer: $this->makeEmpty(RenderService::class, [
                'render' => Expected::once($response),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildQueryRedirect' => Expected::never(),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class),
            verifyLocation: $this->makeEmpty(VerifyLocation::class, ['getFor' => '/verify']),
            templateName: 'Login.latte',
        );

        verify($instance($request, $response, []))->same($response);
    }

    /**
     * @param array<string, mixed> $loginService
     * @param array<string, mixed> $userVerificationService
     * @param array<string, mixed> $responseEmitter
     */
    private function buildActionForUnverifiedUser(bool $customValueCheck, array $loginService, array $userVerificationService, array $responseEmitter, Response $response): Login
    {
        return new Login(
            authenticationService: $this->makeEmpty(NullAuthenticationService::class, ['isAuthenticated' => false]),
            configService: $this->makeEmpty(ConfigService::class, ['get' => 'email']),
            inputValidationService: $this->makeEmpty(InputValidationService::class, [
                'validate' => ['username' => 'user@example.com', 'password' => 'secret'],
            ]),
            loginExtension: $this->makeEmpty(LoginExtensionInterface::class, [
                'getCustomParams' => [],
                'preValidate' => true,
                'customValueCheck' => Expected::once($customValueCheck),
            ]),
            loginService: $this->makeEmpty(LoginService::class, $loginService),
            messageService: $this->makeEmpty(MessageService::class),
            renderer: $this->makeEmpty(RenderService::class, [
                'render' => $response,
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, $responseEmitter),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, $userVerificationService),
            verifyLocation: $this->makeEmpty(VerifyLocation::class, ['getFor' => '/verify']),
            templateName: 'Login.latte',
        );
    }

    public function testUnverifiedUserGetsNoCodeWhenCustomValueCheckFails()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, [
            'getAttribute' => true,
            'getParam' => '',
            'getParams' => [],
        ]);

        $instance = $this->buildActionForUnverifiedUser(
            false,
            [
                'validate' => $user,
                'authenticate' => Expected::never(),
            ],
            [
                'sendVerifyMail' => Expected::never(),
            ],
            [
                'buildQueryRedirect' => Expected::never(),
            ],
            $response,
        );

        verify($instance($request, $response, []))->same($response);
    }

    public function testUnverifiedUserGetsCodeAfterCustomValueCheckPasses()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, [
            'getAttribute' => true,
            'getParam' => '',
            'getParams' => [],
        ]);

        $instance = $this->buildActionForUnverifiedUser(
            true,
            [
                'validate' => $user,
                'authenticate' => Expected::once(function () use ($user) {
                    throw new UserVerificationRequiredException($user);
                }),
            ],
            [
                'sendVerifyMail' => Expected::once('new-guid'),
            ],
            [
                'buildQueryRedirect' => Expected::once(function ($template, $values, $query, $type, $message) use ($response) {
                    verify($query)->equals(['guid' => 'new-guid']);
                    verify($message)->equals('verify.code_sent');

                    return $response;
                }),
            ],
            $response,
        );

        verify($instance($request, $response, []))->same($response);
    }
}
