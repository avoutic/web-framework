<?php

namespace Tests\Unit\Actions;

use Carbon\Carbon;
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
use WebFramework\Security\VerifyLocation;

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
            configService: $this->makeEmpty(ConfigService::class, [
                'get' => fn ($key) => ($key === 'security.email_verification.resend_cooldown_seconds') ? 60 : '/location',
            ]),
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
            verifyLocation: $this->makeEmpty(VerifyLocation::class, [
                'getFor' => fn (string $action) => "/verify-{$action}",
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

    private function buildActionForCooldown(int $cooldownSeconds, int $codeAgeSeconds, ResponseEmitter $responseEmitter, mixed $sendVerifyMail): VerifyResend
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $verificationCode = $this->makeEmpty(VerificationCode::class, [
            'getAction' => 'register',
            'getCreatedAt' => Carbon::now()->getTimestamp() - $codeAgeSeconds,
            'getGuid' => 'old-guid',
            'getUserId' => 1,
            'getFlowData' => ['after_verify_data' => []],
        ]);

        return new VerifyResend(
            accountStatusChecker: $this->makeEmpty(AccountStatusChecker::class, ['isEnabled' => true]),
            authenticationService: $this->makeEmpty(NullAuthenticationService::class),
            changeEmailService: $this->makeEmpty(ChangeEmailService::class),
            configService: $this->makeEmpty(ConfigService::class, [
                'get' => fn ($key) => ($key === 'security.email_verification.resend_cooldown_seconds') ? $cooldownSeconds : '/location',
            ]),
            resetPasswordService: $this->makeEmpty(ResetPasswordService::class),
            responseEmitter: $responseEmitter,
            userRepository: $this->makeEmpty(UserRepository::class, [
                'find' => $this->makeEmpty(User::class),
            ]),
            userVerificationService: $this->makeEmpty(UserVerificationService::class, [
                'sendVerifyMail' => $sendVerifyMail,
            ]),
            verifyLocation: $this->makeEmpty(VerifyLocation::class, [
                'getFor' => fn (string $action) => "/verify-{$action}",
            ]),
            verificationCodeRepository: $this->makeEmpty(VerificationCodeRepository::class, [
                'getByGuid' => $verificationCode,
            ]),
        );
    }

    public function testResendWithinConfiguredCooldownIsRefused()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildActionForCooldown(120, 90, $this->makeEmpty(ResponseEmitter::class, [
            'buildQueryRedirect' => Expected::once(function ($template, $values, $query, $type, $message) use ($response) {
                verify($query)->equals(['guid' => 'old-guid']);
                verify($type)->equals('error');
                verify($message)->equals('verify.resend_cooldown');

                return $response;
            }),
        ]), Expected::never());

        verify($instance($request, $response, []))->same($response);
    }

    public function testResendAfterConfiguredCooldownSendsNewCode()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildActionForCooldown(10, 30, $this->makeEmpty(ResponseEmitter::class, [
            'buildQueryRedirect' => Expected::once(function ($template, $values, $query, $type, $message) use ($response) {
                verify($query)->equals(['guid' => 'new-guid']);
                verify($message)->equals('verify.code_resent');

                return $response;
            }),
        ]), Expected::once('new-guid'));

        verify($instance($request, $response, []))->same($response);
    }

    public function testResendRedirectsToTheLocationOfTheAction()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildActionForCooldown(10, 30, $this->makeEmpty(ResponseEmitter::class, [
            'buildQueryRedirect' => Expected::once(function ($template) use ($response) {
                verify($template)->equals('/verify-register');

                return $response;
            }),
        ]), Expected::once('new-guid'));

        verify($instance($request, $response, []))->same($response);
    }

    public function testCooldownRedirectsToTheLocationOfTheAction()
    {
        $response = $this->makeEmpty(Response::class);
        $request = $this->makeEmpty(Request::class, ['getAttribute' => true, 'getParam' => 'old-guid']);

        $instance = $this->buildActionForCooldown(120, 90, $this->makeEmpty(ResponseEmitter::class, [
            'buildQueryRedirect' => Expected::once(function ($template) use ($response) {
                verify($template)->equals('/verify-register');

                return $response;
            }),
        ]), Expected::never());

        verify($instance($request, $response, []))->same($response);
    }
}
