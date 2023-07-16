<?php

namespace Tests\Unit;

use WebFramework\Validation\MaxLengthRule;

/**
 * @internal
 *
 * @coversNothing
 */
final class MaxLengthRuleTest extends \Codeception\Test\Unit
{
    public function testValid()
    {
        $instance = $this->construct(
            MaxLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('abc'))
            ->equals(true);
    }

    public function testValidEmpty()
    {
        $instance = $this->construct(
            MaxLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid(''))
            ->equals(true);
    }

    public function testValidLimit()
    {
        $instance = $this->construct(
            MaxLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('ABCDEFGHIJ'))
            ->equals(true);
    }

    public function testInvalid()
    {
        $instance = $this->construct(
            MaxLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('ABCDEFGHIJK'))
            ->equals(false);
    }
}
