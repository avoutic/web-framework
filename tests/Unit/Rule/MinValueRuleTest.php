<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Validation\Rule\MinValueRule;

/**
 * @internal
 *
 * @covers \WebFramework\Validation\Rule\MinValueRule
 */
final class MinValueRuleTest extends Unit
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
            ->equals(true)
        ;
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
            ->equals(false)
        ;
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
            ->equals(true)
        ;
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
            ->equals(false)
        ;
    }

    public function testMessage()
    {
        $instance = $this->construct(
            MinValueRule::class,
            [
                10,
            ]
        );

        verify($instance->getErrorMessage())->equals('validation.min_value');
        verify($instance->getErrorParams('test'))->equals([
            'field_name' => 'test',
            'min_value' => '10',
        ]);
    }
}
