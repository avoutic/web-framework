<?php

namespace Tests\Unit\Actions;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Actions\VerifyResend;
use WebFramework\Config\ConfigService;
use WebFramework\Entity\User;
use WebFramework\Entity\VerificationCode;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\AccountStatusChecker;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Security\NullAuthenticationService;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\UserVerificationService;

/**
 * @internal
 *
 * @covers \WebFramework\Actions\VerifyResend
 */
final class VerifyResendTest extends Unit
{
    private function buildAction(string $action, ResponseEmitter $responseEmitter): VerifyResend
    {
        $verificationCode = $this->makeEmpty(VerificationCode::class, [
            'getAction' => $action,
            'getCreatedAt' => 1,
            'getGuid' => 'old-guid',
            'getUserId' => 1,
            'getFlowData' => ['after_verify_data' => []],
            'markAsInvalidated' => Expected::never(),
        ]);

        return new VerifyResend(
            accountStatusChecker: $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => false]),
            authenticationService: $this->makeEmpty(NullAuthenticationService::class),
            changeEmailService: $this->makeEmpty(ChangeEmailService::class),
            configService: $this->makeEmpty(ConfigService::class, ['get' => '/location']),
            resetPasswordService: $this->makeEmpty(ResetPasswordService::class, [
                'sendPasswordResetMail' => Expected::never(),
            ]),
            responseEmitter: $responseEmitter,
            userRepository: $this->makeEmpty(UserRepository::class, [
                'find' => $this->makeEmpty(User::class),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, [
                'sendVerifyMail' => Expected::never(),
            ]),
            verificationCodeRepository: $this->makeEmpty(VerificationCodeRepository::class, [
                'getByGuid' => $verificationCode,
                'save' => Expected::never(),
            ]),
        );
    }

    public function testLoginResendForDisabledAccountRedirectsToLoginWithError()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildAction('login', $this->makeEmpty(ResponseEmitter::class, [
            'buildQueryRedirect' => Expected::never(),
            'buildRedirect' => Expected::once(function ($template, $values, $type, $message) use ($response) {
                verify($type)->equals('error');
                verify($message)->equals('login.account_disabled');

                return $response;
            }),
        ]));

        verify($instance($request, $response, []))->same($response);
    }

    public function testResetPasswordResendForDisabledAccountSendsNothingAndDoesNotRevealIt()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildAction('reset_password', $this->makeEmpty(ResponseEmitter::class, [
            'buildRedirect' => Expected::never(),
            'buildQueryRedirect' => Expected::once(function ($template, $values, $query, $type, $message) use ($response) {
                verify($query)->equals(['guid' => 'old-guid']);
                verify($type)->equals('success');
                verify($message)->equals('verify.code_resent');

                return $response;
            }),
        ]));

        verify($instance($request, $response, []))->same($response);
    }
}
