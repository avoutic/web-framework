<?php

namespace Tests\Unit\Actions;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Actions\ResetPassword;
use WebFramework\Actions\ResetPasswordVerify;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\AccountDisabledException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Presentation\MessageService;
use WebFramework\Presentation\RenderService;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\Extension\ResetPasswordExtensionInterface;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\VerifyLocation;
use WebFramework\Support\UuidProvider;
use WebFramework\Validation\InputValidationService;

/**
 * @internal
 *
 * @covers \WebFramework\Actions\ResetPassword
 * @covers \WebFramework\Actions\ResetPasswordVerify
 */
final class ResetPasswordTest extends Unit
{
    public function testDisabledAccountGetsSameResponseAsUnknownUser()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, [
            'getAttribute' => true,
            'getParams' => [],
        ]);

        $instance = new ResetPassword(
            configService: $this->makeEmpty(ConfigService::class, ['get' => 'username']),
            inputValidationService: $this->makeEmpty(InputValidationService::class, [
                'validate' => ['username' => 'someone'],
            ]),
            messageService: $this->makeEmpty(MessageService::class, [
                'add' => Expected::never(),
            ]),
            renderer: $this->makeEmpty(RenderService::class, [
                'render' => Expected::never(),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildQueryRedirect' => Expected::once(function ($template, $values, $query, $type, $message) use ($response) {
                    verify($query)->equals(['guid' => 'random-guid']);
                    verify($type)->equals('success');
                    verify($message)->equals('reset_password.reset_link_mailed');

                    return $response;
                }),
            ]),
            resetPasswordExtension: $this->makeEmpty(ResetPasswordExtensionInterface::class, [
                'getCustomParams' => [],
                'customValueCheck' => true,
            ]),
            resetPasswordService: $this->makeEmpty(ResetPasswordService::class, [
                'sendPasswordResetMail' => Expected::once(function () use ($user) {
                    throw new AccountDisabledException($user);
                }),
            ]),
            userRepository: $this->makeEmpty(UserRepository::class, [
                'getUserByUsername' => $user,
            ]),
            uuidProvider: $this->makeEmpty(UuidProvider::class, [
                'generate' => Expected::once('random-guid'),
            ]),
            verifyLocation: $this->makeEmpty(VerifyLocation::class, ['getFor' => '/verify']),
            templateName: 'ResetPassword.latte',
        );

        verify($instance($request, $response, []))->same($response);
    }

    public function testVerifyForDisabledAccountRedirectsToLoginWithError()
    {
        $user = $this->makeEmpty(User::class);
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getParam' => 'guid']);

        $instance = new ResetPasswordVerify(
            configService: $this->makeEmpty(ConfigService::class, ['get' => '/login']),
            resetPasswordService: $this->makeEmpty(ResetPasswordService::class, [
                'handleData' => Expected::once(function () use ($user) {
                    throw new AccountDisabledException($user);
                }),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildRedirect' => Expected::once(function ($template, $values, $type, $message) use ($response) {
                    verify($template)->equals('/login');
                    verify($type)->equals('error');
                    verify($message)->equals('login.account_disabled');

                    return $response;
                }),
            ]),
        );

        verify($instance($request, $response, []))->same($response);
    }
}
