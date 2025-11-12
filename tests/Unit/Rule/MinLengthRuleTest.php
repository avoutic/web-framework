<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Validation\Rule\MinLengthRule;

/**
 * @internal
 *
 * @covers \WebFramework\Validation\Rule\MinLengthRule
 */
final class MinLengthRuleTest extends Unit
{
    public function testValid()
    {
        $instance = $this->construct(
            MinLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('abcdefghijklm'))
            ->equals(true)
        ;
    }

    public function testInvalidEmpty()
    {
        $instance = $this->construct(
            MinLengthRule::class,
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
            MinLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('ABCDEFGHIJ'))
            ->equals(true)
        ;
    }

    public function testInvalid()
    {
        $instance = $this->construct(
            MinLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->isValid('ABCDEFGHI'))
            ->equals(false)
        ;
    }

    public function testMessage()
    {
        $instance = $this->construct(
            MinLengthRule::class,
            [
                10,
            ]
        );

        verify($instance->getErrorMessage())->equals('validation.min_length');
        verify($instance->getErrorParams('test'))->equals([
            'field_name' => 'test',
            'min_length' => '10',
        ]);
    }
}
