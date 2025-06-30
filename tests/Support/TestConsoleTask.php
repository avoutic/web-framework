<?php

namespace Tests\Support;

use WebFramework\Task\ConsoleTask;

class TestConsoleTask extends ConsoleTask
{
    public function getCommand(): string
    {
        return 'test:console';
    }

    public function getDescription(): string
    {
        return 'Test console task';
    }

    public function execute(): void
    {
        echo 'Test console task executed successfully!' . PHP_EOL;
    }
}
