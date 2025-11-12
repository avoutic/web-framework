<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Entity\User;
use WebFramework\Entity\VerificationCode;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Exception\InvalidCodeException;
use WebFramework\Repository\UserRepository;
use WebFramework\Repository\VerificationCodeRepository;
use WebFramework\Security\RandomProvider;
use WebFramework\Security\UserCodeService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\UserCodeService
 */
final class UserCodeServiceTest extends Unit
{
    public function testGenerateVerificationCodeEntry()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $user = $this->make(User::class, [
            'id' => 123,
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'guid' => 'test-guid-123',
        ]);

        $repository = $this->make(VerificationCodeRepository::class, [
            'create' => Expected::once($verificationCode),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
                'generateVerificationCode' => Expected::once('ABCDEF'),
            ]
        );

        $result = $instance->generateVerificationCodeEntry($user, 'register', ['test' => 'data']);

        verify($result['guid'])->equals('test-guid-123');
        verify($result['code'])->equals('ABCDEF');
    }

    public function testVerifyCodeByGuidNotFound()
    {
        $repository = $this->make(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once(null),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify(function () use ($instance) {
            $instance->verifyCodeByGuid('invalid-guid', 'register', '123456');
        })
            ->callableThrows(CodeVerificationException::class)
        ;
    }

    public function testVerifyCodeByGuidWrongAction()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'login',
        ]);

        $repository = $this->make(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once($verificationCode),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify(function () use ($instance) {
            $instance->verifyCodeByGuid('test-guid', 'register', '123456');
        })
            ->callableThrows(CodeVerificationException::class)
        ;
    }

    public function testVerifyCodeByGuidMaxAttemptsExceeded()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'register',
            'attempts' => 5,
            'maxAttempts' => 5,
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'usedAt' => null,
            'createdAt' => Carbon::now()->getTimestamp(),
        ]);

        $repository = $this->make(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once($verificationCode),
            'save' => Expected::once(),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify(function () use ($instance) {
            $instance->verifyCodeByGuid('test-guid', 'register', '123456');
        })
            ->callableThrows(InvalidCodeException::class)
        ;
    }

    public function testVerifyCodeByGuidWrongCode()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'register',
            'code' => '123456',
            'userId' => 123,
            'attempts' => 0,
            'maxAttempts' => 5,
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'createdAt' => Carbon::now()->getTimestamp(),
        ]);

        $repository = $this->makeEmpty(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once($verificationCode),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify(function () use ($instance) {
            $instance->verifyCodeByGuid('test-guid', 'register', 'wrong-code');
        })
            ->callableThrows(InvalidCodeException::class)
        ;
    }

    public function testVerifyCodeByGuidUserNotFound()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'register',
            'code' => '123456',
            'userId' => 999,
            'attempts' => 0,
            'maxAttempts' => 5,
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'usedAt' => null,
            'createdAt' => Carbon::now()->getTimestamp(),
        ]);

        $repository = $this->make(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once($verificationCode),
            'save' => Expected::exactly(2),
        ]);

        $userRepository = $this->make(UserRepository::class, [
            'getObjectById' => Expected::once(null),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $userRepository,
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify(function () use ($instance) {
            $instance->verifyCodeByGuid('test-guid', 'register', '123456');
        })
            ->callableThrows(CodeVerificationException::class)
        ;
    }

    public function testVerifyCodeByGuidSuccess()
    {
        $user = $this->make(User::class, [
            'getId' => Expected::once(123),
        ]);

        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'register',
            'code' => '123456',
            'userId' => 123,
            'attempts' => 0,
            'maxAttempts' => 5,
            'expiresAt' => Carbon::now()->addMinutes(15)->getTimestamp(),
            'usedAt' => null,
            'createdAt' => Carbon::now()->getTimestamp(),
        ]);

        $repository = $this->makeEmpty(VerificationCodeRepository::class, [
            'getActiveByGuid' => Expected::once($verificationCode),
        ]);

        $userRepository = $this->make(UserRepository::class, [
            'getObjectById' => Expected::once($user),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $userRepository,
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        $result = $instance->verifyCodeByGuid('test-guid', 'register', '123456');

        verify($result['user'])->equals($user);
    }

    public function testGetActionByGuid()
    {
        $verificationCode = $this->make(VerificationCode::class, [
            'action' => 'register',
        ]);

        $repository = $this->make(VerificationCodeRepository::class, [
            'getByGuid' => Expected::once($verificationCode),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify($instance->getActionByGuid('test-guid'))->equals('register');
    }

    public function testGetActionByGuidNotFound()
    {
        $repository = $this->make(VerificationCodeRepository::class, [
            'getByGuid' => Expected::once(null),
        ]);

        $instance = $this->make(
            UserCodeService::class,
            [
                'logger' => $this->makeEmpty(LoggerInterface::class),
                'randomProvider' => $this->makeEmpty(RandomProvider::class),
                'userRepository' => $this->makeEmpty(UserRepository::class),
                'verificationCodeRepository' => $repository,
                'codeLength' => 6,
                'codeExpiryMinutes' => 15,
                'maxAttempts' => 5,
            ]
        );

        verify($instance->getActionByGuid('invalid-guid'))->null();
    }
}
