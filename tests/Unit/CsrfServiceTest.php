<?php

namespace Tests\Unit;

use Odan\Session\MemorySession;
use WebFramework\Security\CsrfService;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfServiceTest extends \Codeception\Test\Unit
{
    public function testGetToken()
    {
        $instance = $this->construct(
            CsrfService::class,
            [
                new MemorySession(),
            ],
            [
                'getRandomBytes' => '1234567890123456',
            ]
        );

        verify($instance->getToken())
            ->equals('3132333435363738393031323334353600000000000000000000000000000000');
    }

    public function testValidateToken()
    {
        $instance = $this->make(
            CsrfService::class,
            [
                'isValidTokenStored' => true,
                'getStoredToken' => '1234567890123456',
            ]
        );

        verify($instance->validateToken('3132333435363738393031323334353600000000000000000000000000000000'))
            ->equals(true);
    }

    public function testValidateTokenMismatch()
    {
        $instance = $this->make(
            CsrfService::class,
            [
                'isValidTokenStored' => true,
                'getStoredToken' => '1234567890123457',
            ]
        );

        verify($instance->validateToken('3132333435363738393031323334353600000000000000000000000000000000'))
            ->equals(false);
    }
}
