<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Validation\Rule\FilterRule;

/**
 * @internal
 *
 * @coversNothing
 */
final class FilterRuleTest extends Unit
{
    public function testValid()
    {
        $instance = $this->construct(
            FilterRule::class,
            [
                '[a-z]+',
            ]
        );

        verify($instance->isValid('abc'))
            ->equals(true)
        ;
    }

    public function testValidEmpty()
    {
        $instance = $this->construct(
            FilterRule::class,
            [
                '[a-z]*',
            ]
        );

        verify($instance->isValid(''))
            ->equals(true)
        ;
    }

    public function testInvalidEmpty()
    {
        $instance = $this->construct(
            FilterRule::class,
            [
                '[a-z]+',
            ]
        );

        verify($instance->isValid(''))
            ->equals(false)
        ;
    }

    public function testInvalid()
    {
        $instance = $this->construct(
            FilterRule::class,
            [
                '[a-z]+',
            ]
        );

        verify($instance->isValid('ABC'))
            ->equals(false)
        ;
    }
}
