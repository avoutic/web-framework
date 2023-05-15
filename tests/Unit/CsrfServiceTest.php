<?php

namespace Tests\Unit;

use WebFramework\Core\Security\CsrfService;

/**
 * @internal
 *
 * @coversNothing
 */
final class CsrfServiceTest extends \Codeception\Test\Unit
{
    public function testGetToken()
    {
        $instance = $this->make(
            CsrfService::class,
            [
                'get_random_bytes' => '1234567890123456',
            ]
        );

        verify($instance->get_token())
            ->equals('3132333435363738393031323334353600000000000000000000000000000000');
    }

    public function testValidateToken()
    {
        $instance = $this->make(
            CsrfService::class,
            [
                'is_valid_token_stored' => true,
                'get_stored_token' => '1234567890123456',
            ]
        );

        verify($instance->validate_token('3132333435363738393031323334353600000000000000000000000000000000'))
            ->equals(true);
    }

    public function testValidateTokenMismatch()
    {
        $instance = $this->make(
            CsrfService::class,
            [
                'is_valid_token_stored' => true,
                'get_stored_token' => '1234567890123457',
            ]
        );

        verify($instance->validate_token('3132333435363738393031323334353600000000000000000000000000000000'))
            ->equals(false);
    }
}
