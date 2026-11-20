<?php

namespace Tests\Unit\Actions;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Actions\RegisterVerify;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Exception\LoginRefusedException;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Security\Extension\RegisterExtensionInterface;
use WebFramework\Security\LoginService;
use WebFramework\Security\UserVerificationService;

/**
 * @internal
 *
 * @covers \WebFramework\Actions\RegisterVerify
 */
final class RegisterVerifyTest extends Unit
{
    public function testCompletesLoginThroughLoginService()
    {
        $user = $this->makeEmpty(User::class);
        $request = $this->makeEmpty(Request::class, ['getParam' => 'guid']);
        $response = $this->makeEmpty(Response::class);

        $instance = new RegisterVerify(
            configService: $this->makeEmpty(ConfigService::class, ['get' => '/welcome']),
            loginService: $this->makeEmpty(LoginService::class, [
                'completeLogin' => Expected::once(function ($passedRequest, $passedUser) {
                    verify($passedRequest)->instanceOf(Request::class);
                    verify($passedUser)->instanceOf(User::class);
                }),
            ]),
            registerExtension: $this->makeEmpty(RegisterExtensionInterface::class, [
                'postVerify' => Expected::once(function ($passedUser, $params) {
                    verify($passedUser)->instanceOf(User::class);
                    verify($params)->equals(['plan' => 'pro']);
                }),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildRedirect' => Expected::once(function ($template, $values, $type, $message) use ($response) {
                    verify($template)->equals('/welcome');
                    verify($type)->equals('success');
                    verify($message)->equals('verify.success');

                    return $response;
                }),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, [
                'handleData' => Expected::once([
                    'user' => $user,
                    'after_verify_data' => ['plan' => 'pro'],
                    'first_verification' => true,
                ]),
            ]),
        );

        verify($instance($request, $response, []))->same($response);
    }

    public function testRefusedLoginStillCallsPostVerifyAndRedirectsToLoginWithError()
    {
        $user = $this->makeEmpty(User::class);
        $request = $this->makeEmpty(Request::class, ['getParam' => 'guid']);
        $response = $this->makeEmpty(Response::class);

        $instance = new RegisterVerify(
            configService: $this->makeEmpty(ConfigService::class, ['get' => '/login']),
            loginService: $this->makeEmpty(LoginService::class, [
                'completeLogin' => Expected::once(function () use ($user) {
                    throw new LoginRefusedException($user);
                }),
            ]),
            registerExtension: $this->makeEmpty(RegisterExtensionInterface::class, [
                'postVerify' => Expected::once(function ($passedUser, $params) {
                    verify($passedUser)->instanceOf(User::class);
                    verify($params)->equals(['plan' => 'pro']);
                }),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildRedirect' => Expected::once(function ($template, $values, $type, $message) use ($response) {
                    verify($template)->equals('/login');
                    verify($type)->equals('error');
                    verify($message)->equals('login.refused');

                    return $response;
                }),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, [
                'handleData' => Expected::once([
                    'user' => $user,
                    'after_verify_data' => ['plan' => 'pro'],
                    'first_verification' => true,
                ]),
            ]),
        );

        verify($instance($request, $response, []))->same($response);
    }

    public function testAlreadyVerifiedUserSkipsPostVerify()
    {
        $user = $this->makeEmpty(User::class);
        $request = $this->makeEmpty(Request::class, ['getParam' => 'guid']);
        $response = $this->makeEmpty(Response::class);

        $instance = new RegisterVerify(
            configService: $this->makeEmpty(ConfigService::class, ['get' => '/welcome']),
            loginService: $this->makeEmpty(LoginService::class, [
                'completeLogin' => Expected::once(),
            ]),
            registerExtension: $this->makeEmpty(RegisterExtensionInterface::class, [
                'postVerify' => Expected::never(),
            ]),
            responseEmitter: $this->makeEmpty(ResponseEmitter::class, [
                'buildRedirect' => Expected::once($response),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, [
                'handleData' => Expected::once([
                    'user' => $user,
                    'after_verify_data' => ['plan' => 'pro'],
                    'first_verification' => false,
                ]),
            ]),
        );

        verify($instance($request, $response, []))->same($response);
    }
}
