<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Validation\Rule\MaxValueRule;

/**
 * @internal
 *
 * @covers \WebFramework\Validation\Rule\MaxValueRule
 */
final class MaxValueRuleTest extends Unit
{
    public function testValid()
    {
        $instance = $this->construct(
            MaxValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('9'))
            ->equals(true)
        ;
    }

    public function testInvalidString()
    {
        $instance = $this->construct(
            MaxValueRule::class,
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
            MaxValueRule::class,
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
            MaxValueRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('11'))
            ->equals(false)
        ;
    }

    public function testMessage()
    {
        $instance = $this->construct(
            MaxValueRule::class,
            [
                10,
            ]
        );

        verify($instance->getErrorMessage())->equals('validation.max_value');
        verify($instance->getErrorParams('test'))->equals([
            'field_name' => 'test',
            'max_value' => '10',
        ]);
    }
}
