<?php

namespace Tests\Unit;

use WebFramework\Validation\MinValueRule;

/**
 * @internal
 *
 * @coversNothing
 */
final class MinValueRuleTest extends \Codeception\Test\Unit
{
    public function testValid()
    {
        $instance = $this->construct(
            MinValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('11'))
            ->equals(true);
    }

    public function testInvalidString()
    {
        $instance = $this->construct(
            MinValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid(''))
            ->equals(false);
    }

    public function testValidLimit()
    {
        $instance = $this->construct(
            MinValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('10'))
            ->equals(true);
    }

    public function testInvalid()
    {
        $instance = $this->construct(
            MinValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('9'))
            ->equals(false);
    }
}
