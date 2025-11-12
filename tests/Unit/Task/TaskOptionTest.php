<?php

namespace Tests\Unit\Task;

use Codeception\Test\Unit;
use WebFramework\Task\TaskOption;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskOption
 */
final class TaskOptionTest extends Unit
{
    public function testApplyValueWithValidOption()
    {
        $receivedValue = null;
        $setter = function (string $value) use (&$receivedValue): void {
            $receivedValue = $value;
        };

        $option = new TaskOption('test-option', 't', 'Description', true, $setter);
        $option->applyValue('test-value');

        verify($receivedValue)->equals('test-value');
    }

    public function testApplyValueThrowsExceptionWhenHasValueIsFalse()
    {
        $setter = function (): void {};
        $option = new TaskOption('test-option', 't', 'Description', false, $setter);

        verify(function () use ($option) {
            $option->applyValue('test-value');
        })->callableThrows(\BadMethodCallException::class, 'Option does not support a value');
    }

    public function testApplyValueWithDifferentValues()
    {
        $receivedValues = [];
        $setter = function (string $value) use (&$receivedValues): void {
            $receivedValues[] = $value;
        };

        $option = new TaskOption('test-option', 't', 'Description', true, $setter);
        $option->applyValue('value1');
        $option->applyValue('value2');

        verify($receivedValues)->equals(['value1', 'value2']);
    }

    public function testTriggerWithValidFlag()
    {
        $triggered = false;
        $setter = function () use (&$triggered): void {
            $triggered = true;
        };

        $option = new TaskOption('test-option', 't', 'Description', false, $setter);
        $option->trigger();

        verify($triggered)->equals(true);
    }

    public function testTriggerThrowsExceptionWhenHasValueIsTrue()
    {
        $setter = function (string $value): void {};
        $option = new TaskOption('test-option', 't', 'Description', true, $setter);

        verify(function () use ($option) {
            $option->trigger();
        })->callableThrows(\BadMethodCallException::class, 'Option requires a value');
    }

    public function testTriggerMultipleTimes()
    {
        $triggerCount = 0;
        $setter = function () use (&$triggerCount): void {
            $triggerCount++;
        };

        $option = new TaskOption('test-option', 't', 'Description', false, $setter);
        $option->trigger();
        $option->trigger();
        $option->trigger();

        verify($triggerCount)->equals(3);
    }

    public function testGetDisplayNameWithShortName()
    {
        $setter = function (string $value): void {};
        $option = new TaskOption('test-option', 't', 'Description', true, $setter);
        verify($option->getDisplayName())->equals('--test-option | -t');
    }

    public function testGetDisplayNameWithoutShortName()
    {
        $setter = function (string $value): void {};
        $option = new TaskOption('test-option', null, 'Description', true, $setter);
        verify($option->getDisplayName())->equals('--test-option');
    }

    public function testGetDisplayNameWithEmptyShortName()
    {
        $setter = function (string $value): void {};
        $option = new TaskOption('test-option', '', 'Description', true, $setter);
        verify($option->getDisplayName())->equals('--test-option');
    }
}
