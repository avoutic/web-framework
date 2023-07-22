<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use WebFramework\Exception\CodeVerificationException;
use WebFramework\Security\ProtectService;
use WebFramework\Security\UserCodeService;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserCodeServiceTest extends \Codeception\Test\Unit
{
    public function testVerifyNoCode()
    {
        $instance = $this->make(
            UserCodeService::class,
        );

        verify(function () use ($instance) {
            $instance->verify('', '', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifyNoArray()
    {
        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once(false),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->verify('packed', '', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifyNoAction()
    {
        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once([]),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->verify('packed', '', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifyWrongAction()
    {
        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once(['action' => 'otherAction']),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->verify('packed', 'thisAction', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifyNoTimestamp()
    {
        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once(['action' => 'thisAction']),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->verify('packed', 'thisAction', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifyExpiredTimestamp()
    {
        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once([
                            'action' => 'thisAction',
                            'timestamp' => time() - 1,
                        ]),
                    ],
                ),
            ],
        );

        verify(function () use ($instance) {
            $instance->verify('packed', 'thisAction', 0);
        })
            ->callableThrows(CodeVerificationException::class);
    }

    public function testVerifySuccess()
    {
        $data = [
            'action' => 'thisAction',
            'timestamp' => time() - 1,
            'params' => [],
            'other' => 'than',
        ];

        $instance = $this->make(
            UserCodeService::class,
            [
                'protectService' => $this->makeEmpty(
                    ProtectService::class,
                    [
                        'unpackArray' => Expected::once($data),
                    ],
                ),
            ],
        );

        verify($instance->verify('packed', 'thisAction', 1))
            ->equals($data);
    }
}
