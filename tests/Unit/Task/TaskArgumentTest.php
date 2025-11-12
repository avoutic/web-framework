<?php

namespace Tests\Unit\Task;

use Codeception\Test\Unit;
use WebFramework\Task\TaskArgument;

/**
 * @internal
 *
 * @covers \WebFramework\Task\TaskArgument
 */
final class TaskArgumentTest extends Unit
{
    public function testApplyCallsSetter()
    {
        $receivedValue = null;
        $setter = function (string $value) use (&$receivedValue): void {
            $receivedValue = $value;
        };

        $argument = new TaskArgument('testArg', 'Description', true, $setter);
        $argument->apply('test-value');

        verify($receivedValue)->equals('test-value');
    }

    public function testApplyWithDifferentValues()
    {
        $receivedValues = [];
        $setter = function (string $value) use (&$receivedValues): void {
            $receivedValues[] = $value;
        };

        $argument = new TaskArgument('testArg', 'Description', true, $setter);
        $argument->apply('value1');
        $argument->apply('value2');
        $argument->apply('value3');

        verify($receivedValues)->equals(['value1', 'value2', 'value3']);
    }

    public function testApplyWithEmptyString()
    {
        $receivedValue = null;
        $setter = function (string $value) use (&$receivedValue): void {
            $receivedValue = $value;
        };

        $argument = new TaskArgument('testArg', 'Description', true, $setter);
        $argument->apply('');

        verify($receivedValue)->equals('');
    }
}
