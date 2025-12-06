<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Entity\VerificationCode;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\PasswordHashService;
use WebFramework\Security\RandomProvider;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\SecurityIteratorService;
use WebFramework\Security\UserCodeService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\ResetPasswordService
 */
final class ResetPasswordServiceTest extends Unit
{
    public function testUpdatePassword()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'solidPassword' => 'sha256:1000:salt123:hash123',
        ]);

        $passwordHashService = $this->makeEmpty(
            PasswordHashService::class,
            [
                'generateHash' => Expected::once('sha256:1000:newsalt:newhash'),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'save' => Expected::once(),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'incrementFor' => Expected::once(1),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'passwordHashService' => $passwordHashService,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $service->updatePassword($user, 'newpassword123');

        verify($user->getSolidPassword())->equals('sha256:1000:newsalt:newhash');
    }

    public function testSendPasswordResetMail()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'getEmail' => 'test@example.com',
            'toArray' => ['id' => 1, 'username' => 'testuser'],
        ]);

        $userCodeService = $this->makeEmpty(
            UserCodeService::class,
            [
                'generateVerificationCodeEntry' => Expected::once(function ($userArg, $action, $flowData) use ($user) {
                    verify($userArg)->equals($user);
                    verify($action)->equals('reset_password');
                    verify($flowData['iterator'])->equals(1);

                    return ['guid' => 'test-guid-123', 'code' => 'ABC123'];
                }),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'incrementFor' => Expected::once(1),
            ],
        );

        $userMailer = $this->makeEmpty(
            UserMailer::class,
            [
                'passwordReset' => Expected::once(function ($email, $params) {
                    verify($email)->equals('test@example.com');
                    verify($params['code'])->equals('ABC123');

                    return true;
                }),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'userCodeService' => $userCodeService,
                'securityIteratorService' => $securityIteratorService,
                'userMailer' => $userMailer,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'codeExpiryMinutes' => 15,
            ],
        );

        $result = $service->sendPasswordResetMail($user);

        verify($result)->equals('test-guid-123');
    }

    public function testHandleDataSuccess()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'reset_password',
            'userId' => 1,
            'flowData' => json_encode(['iterator' => 1]),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
                'save' => Expected::once(),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'find' => Expected::once($user),
                'save' => Expected::never(),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'getFor' => Expected::once(1),
            ],
        );

        $authenticationService = $this->makeEmpty(
            AuthenticationService::class,
            [
                'invalidateSessions' => Expected::once(),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'authenticationService' => $authenticationService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'sendNewPassword' => Expected::once(true),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->handleData($request, 'test-guid-123');
    }

    public function testHandleDataThrowsExceptionWhenVerificationCodeNotFound()
    {
        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once(null),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'invalid-guid');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenCodeNotCorrect()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => null,
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenCodeExpired()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->subMinutes(1)->getTimestamp(),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenCodeInvalidated()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => Carbon::now()->getTimestamp(),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenCodeProcessed()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => Carbon::now()->getTimestamp(),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenActionMismatch()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'register',
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'error' => Expected::once(),
        ]);

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $logger,
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenUserNotFound()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'reset_password',
            'userId' => 1,
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'find' => Expected::once(null),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenIteratorMismatch()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'reset_password',
            'userId' => 1,
            'flowData' => json_encode(['iterator' => 1]),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'find' => Expected::once($user),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'getFor' => Expected::once(2),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testSendNewPassword()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'test@example.com',
        ]);

        $randomProvider = $this->makeEmpty(
            RandomProvider::class,
            [
                'getRandom' => Expected::once(hex2bin('12345678901234567890123456789012')),
            ],
        );

        $userMailer = $this->makeEmpty(
            UserMailer::class,
            [
                'newPassword' => Expected::once(function ($email, $params) {
                    verify($email)->equals('test@example.com');
                    verify($params['password'])->equals('12345678901234567890');

                    return true;
                }),
            ],
        );

        $service = $this->make(
            ResetPasswordService::class,
            [
                'randomProvider' => $randomProvider,
                'userMailer' => $userMailer,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'updatePassword' => Expected::once(function ($userArg, $newPassword) use ($user) {
                    verify($userArg)->equals($user);
                    verify($newPassword)->equals('12345678901234567890');
                }),
            ],
        );

        $result = $service->sendNewPassword($user);

        verify($result)->true();
    }
}
