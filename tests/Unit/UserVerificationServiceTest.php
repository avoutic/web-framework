<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;
use WebFramework\Entity\VerificationCode;
use WebFramework\Event\EventService;
use WebFramework\Event\UserVerified;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\UserCodeService;
use WebFramework\Security\UserVerificationService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\UserVerificationService
 */
final class UserVerificationServiceTest extends Unit
{
    public function testSendVerifyMail()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'getEmail' => Expected::once('test@example.com'),
            'toArray' => Expected::once(['id' => 1, 'username' => 'testuser']),
        ]);

        $userCodeService = $this->makeEmpty(
            UserCodeService::class,
            [
                'generateVerificationCodeEntry' => Expected::once(function ($userArg, $action, $flowData) use ($user) {
                    verify($userArg)->equals($user);
                    verify($action)->equals('register');
                    verify($flowData['after_verify_data'])->equals(['redirect' => '/dashboard']);

                    return ['guid' => 'test-guid-123', 'code' => 'ABC123'];
                }),
            ],
        );

        $userMailer = $this->makeEmpty(
            UserMailer::class,
            [
                'emailVerificationCode' => Expected::once(function ($email, $params) {
                    verify($email)->equals('test@example.com');
                    verify($params['code'])->equals('ABC123');
                    verify($params['user'])->equals(['id' => 1, 'username' => 'testuser']);
                    verify($params['validity'])->equals(15);

                    return true;
                }),
            ],
        );

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'debug' => Expected::once(),
        ]);

        $service = $this->make(
            UserVerificationService::class,
            [
                'userCodeService' => $userCodeService,
                'userMailer' => $userMailer,
                'logger' => $logger,
                'codeExpiryMinutes' => 15,
            ],
        );

        $result = $service->sendVerifyMail($user, 'register', ['redirect' => '/dashboard']);

        verify($result)->equals('test-guid-123');
    }

    public function testHandleDataWithUnverifiedUser()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->makeEmpty(User::class, [
            'getId' => 1,
            'isVerified' => Expected::once(false),
            'setVerified' => Expected::once(function ($timestamp) {
                verify($timestamp)->greaterThan(0);
            }),
        ]);

        $verificationCode = $this->makeEmpty(VerificationCode::class, [
            'isCorrect' => Expected::once(true),
            'isExpired' => Expected::once(false),
            'isInvalidated' => Expected::once(false),
            'isProcessed' => Expected::once(false),
            'getAction' => Expected::once('register'),
            'getUserId' => Expected::once(1),
            'getFlowData' => Expected::once(['after_verify_data' => ['redirect' => '/dashboard']]),
            'markAsProcessed' => Expected::once(),
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
                'getObjectById' => Expected::once($user),
                'save' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(function ($event) {
                    verify($event)->instanceOf(UserVerified::class);
                }),
            ],
        );

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'debug' => Expected::once(),
            'info' => Expected::once(),
        ]);

        $service = $this->make(
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'eventService' => $eventService,
                'logger' => $logger,
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $result = $service->handleData($request, 'test-guid-123', 'register');

        verify($result)->equals([
            'user' => $user,
            'after_verify_data' => ['redirect' => '/dashboard'],
        ]);
    }

    public function testHandleDataWithVerifiedUser()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'verified' => true,
            'verifiedAt' => Carbon::now()->getTimestamp(),
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'action' => 'register',
            'userId' => 1,
            'flowData' => json_encode(['after_verify_data' => []]),
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
                'getObjectById' => Expected::once($user),
                'save' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(EventService::class);

        $logger = $this->makeEmpty(LoggerInterface::class, [
            'debug' => Expected::once(),
            'info' => Expected::once(),
        ]);

        $service = $this->make(
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'eventService' => $eventService,
                'logger' => $logger,
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $result = $service->handleData($request, 'test-guid-123', 'register');

        verify($result['user'])->equals($user);
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'invalid-guid', 'register');
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenActionMismatch()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'reset_password',
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
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $logger,
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataThrowsExceptionWhenUserNotFound()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'register',
            'userId' => 1,
            'flowData' => json_encode(['after_verify_data' => []]),
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
                'getObjectById' => Expected::once(null),
            ],
        );

        $service = $this->make(
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request) {
            $service->handleData($request, 'test-guid-123', 'register');
        })->callableThrows(CodeVerificationException::class);
    }

    public function testHandleDataReturnsEmptyAfterVerifyDataWhenNotSet()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'verified' => true,
            'verifiedAt' => Carbon::now()->getTimestamp(),
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'register',
            'userId' => 1,
            'flowData' => json_encode(['after_verify_data' => []]),
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
                'getObjectById' => Expected::once($user),
                'save' => Expected::once(),
            ],
        );

        $service = $this->make(
            UserVerificationService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $result = $service->handleData($request, 'test-guid-123', 'register');

        verify($result['after_verify_data'])->equals([]);
    }
}
