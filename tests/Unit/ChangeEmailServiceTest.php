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
use WebFramework\Event\UserEmailChanged;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\DuplicateEmailException;
use WebFramework\Exception\WrongAccountException;
use WebFramework\Mail\UserMailer;
use WebFramework\Repository\RepositoryQuery;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Security\SecurityIteratorService;
use WebFramework\Security\UserCodeService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\ChangeEmailService
 */
final class ChangeEmailServiceTest extends Unit
{
    public function testChangeEmailWithUsernameIdentifier()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'username' => 'oldusername',
            'email' => 'oldemail@example.com',
        ]);

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'save' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(function ($event) {
                    verify($event)->instanceOf(UserEmailChanged::class);
                }),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'userRepository' => $userRepository,
                'eventService' => $eventService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'uniqueIdentifier' => 'username',
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->changeEmail($request, $user, 'newemail@example.com');

        verify($user->getEmail())->equals('newemail@example.com');
        verify($user->getUsername())->equals('oldusername');
    }

    public function testChangeEmailWithEmailIdentifier()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'username' => 'oldusername',
            'email' => 'oldemail@example.com',
        ]);

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'query' => Expected::once(function () {
                    return $this->makeEmpty(RepositoryQuery::class, [
                        'where' => Expected::once(function () {
                            return $this->makeEmpty(RepositoryQuery::class, [
                                'exists' => Expected::once(false),
                            ]);
                        }),
                    ]);
                }),
                'save' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'userRepository' => $userRepository,
                'eventService' => $eventService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'uniqueIdentifier' => 'email',
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->changeEmail($request, $user, 'newemail@example.com');

        verify($user->getEmail())->equals('newemail@example.com');
        verify($user->getUsername())->equals('newemail@example.com');
    }

    public function testChangeEmailThrowsExceptionWhenEmailExists()
    {
        $user = $this->make(User::class, [
            'id' => 1,
        ]);

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'query' => Expected::once(function () {
                    return $this->makeEmpty(RepositoryQuery::class, [
                        'where' => Expected::once(function () {
                            return $this->makeEmpty(RepositoryQuery::class, [
                                'exists' => Expected::once(true),
                            ]);
                        }),
                    ]);
                }),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'uniqueIdentifier' => 'email',
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->changeEmail($request, $user, 'existing@example.com');
        })->callableThrows(DuplicateEmailException::class);
    }

    public function testSendChangeEmailVerify()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'oldemail@example.com',
            'username' => 'oldusername',
        ]);

        $userCodeService = $this->makeEmpty(
            UserCodeService::class,
            [
                'generateVerificationCodeEntry' => Expected::once(function ($userArg, $action, $flowData) use ($user) {
                    verify($userArg)->equals($user);
                    verify($action)->equals('change_email');
                    verify($flowData['email'])->equals('newemail@example.com');
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
                'changeEmailVerificationCode' => Expected::once(function ($email, $params) {
                    verify($email)->equals('newemail@example.com');
                    verify($params['code'])->equals('ABC123');

                    return true;
                }),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'query' => Expected::once(function () {
                    return $this->makeEmpty(RepositoryQuery::class, [
                        'where' => Expected::once(function () {
                            return $this->makeEmpty(RepositoryQuery::class, [
                                'exists' => Expected::once(false),
                            ]);
                        }),
                    ]);
                }),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'userCodeService' => $userCodeService,
                'securityIteratorService' => $securityIteratorService,
                'userMailer' => $userMailer,
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'codeExpiryMinutes' => 15,
                'uniqueIdentifier' => 'email',
            ],
        );

        $result = $service->sendChangeEmailVerify($user, 'newemail@example.com');

        verify($result)->equals('test-guid-123');
        verify($user->getEmail())->equals('oldemail@example.com');
        verify($user->getUsername())->equals('oldusername');
    }

    public function testSendChangeEmailVerifyThrowsExceptionWhenEmailExists()
    {
        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'oldemail@example.com',
            'username' => 'oldusername',
        ]);

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'query' => Expected::once(function () {
                    return $this->makeEmpty(RepositoryQuery::class, [
                        'where' => Expected::once(function () {
                            return $this->makeEmpty(RepositoryQuery::class, [
                                'exists' => Expected::once(true),
                            ]);
                        }),
                    ]);
                }),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'userRepository' => $userRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'uniqueIdentifier' => 'email',
            ],
        );

        verify(function () use ($service, $user) {
            $service->sendChangeEmailVerify($user, 'existing@example.com');
        })->callableThrows(DuplicateEmailException::class);
    }

    public function testHandleDataSuccess()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'old@example.com',
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'change_email',
            'userId' => 1,
            'flowData' => json_encode(['email' => 'newemail@example.com', 'iterator' => 1]),
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
                'authenticate' => Expected::once(),
            ],
        );

        $eventService = $this->makeEmpty(
            EventService::class,
            [
                'dispatch' => Expected::once(),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'authenticationService' => $authenticationService,
                'eventService' => $eventService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'uniqueIdentifier' => 'username',
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->handleData($request, $user, 'test-guid-123');

        verify($user->getEmail())->equals('newemail@example.com');
    }

    public function testHandleDataThrowsExceptionWhenVerificationCodeNotFound()
    {
        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once(null),
            ],
        );

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'oldemail@example.com',
        ]);

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->handleData($request, $user, 'invalid-guid');
        })->callableThrows(CodeVerificationException::class);

        verify($user->getEmail())->equals('oldemail@example.com');
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

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'oldemail@example.com',
        ]);

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->handleData($request, $user, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);

        verify($user->getEmail())->equals('oldemail@example.com');
    }

    public function testHandleDataThrowsExceptionWhenWrongAccount()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'oldemail@example.com',
        ]);

        $codeUser = $this->make(User::class, [
            'id' => 2,
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'change_email',
            'userId' => 2,
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
                'getObjectById' => Expected::once($codeUser),
            ],
        );

        $authenticationService = $this->makeEmpty(
            AuthenticationService::class,
            [
                'deauthenticate' => Expected::once(),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'authenticationService' => $authenticationService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->handleData($request, $user, 'test-guid-123');
        })->callableThrows(WrongAccountException::class);

        verify($user->getEmail())->equals('oldemail@example.com');
    }

    public function testHandleDataReturnsEarlyWhenEmailAlreadyChanged()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'newemail@example.com',
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'change_email',
            'userId' => 1,
            'flowData' => json_encode(['email' => 'newemail@example.com', 'iterator' => 1]),
        ]);

        $verificationCodeRepository = $this->makeEmpty(
            VerificationCodeRepository::class,
            [
                'getByGuid' => Expected::once($verificationCode),
                'save' => Expected::never(),
            ],
        );

        $userRepository = $this->makeEmpty(
            UserRepository::class,
            [
                'getObjectById' => Expected::once($user),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'getFor' => Expected::never(),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        $service->handleData($request, $user, 'test-guid-123');

        verify($user->getEmail())->equals('newemail@example.com');
    }

    public function testHandleDataThrowsExceptionWhenIteratorMismatch()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 1,
            'email' => 'old@example.com',
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'correctAt' => Carbon::now()->getTimestamp(),
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'invalidatedAt' => null,
            'processedAt' => null,
            'action' => 'change_email',
            'userId' => 1,
            'flowData' => json_encode(['email' => 'newemail@example.com', 'iterator' => 1]),
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
                'getObjectById' => Expected::once($user),
            ],
        );

        $securityIteratorService = $this->makeEmpty(
            SecurityIteratorService::class,
            [
                'getFor' => Expected::once(2),
            ],
        );

        $service = $this->make(
            ChangeEmailService::class,
            [
                'verificationCodeRepository' => $verificationCodeRepository,
                'userRepository' => $userRepository,
                'securityIteratorService' => $securityIteratorService,
                'logger' => $this->makeEmpty(LoggerInterface::class),
            ],
        );

        $request = $this->makeEmpty(Request::class);

        verify(function () use ($service, $request, $user) {
            $service->handleData($request, $user, 'test-guid-123');
        })->callableThrows(CodeVerificationException::class);
    }
}
