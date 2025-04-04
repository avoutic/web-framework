<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use WebFramework\Core\ConsoleTask;
use WebFramework\Queue\QueueService;

class QueueList extends ConsoleTask
{
    public function __construct(
        private QueueService $queueService,
    ) {}

    public function getCommand(): string
    {
        return 'queue:list';
    }

    public function getDescription(): string
    {
        return 'List all queues';
    }

    public function execute(): void
    {
        $queues = $this->queueService->getQueueNames();

        if (empty($queues))
        {
            echo 'No queues found'.PHP_EOL;

            return;
        }

        echo 'Available queues:'.PHP_EOL;

        foreach ($queues as $queue)
        {
            echo $queue.PHP_EOL;
        }
    }
}
