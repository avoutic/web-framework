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

use WebFramework\Queue\QueueService;

class QueueList extends ConsoleTask
{
    /**
     * QueueList constructor.
     *
     * @param QueueService $queueService The queue service
     * @param resource     $outputStream The output stream
     */
    public function __construct(
        private QueueService $queueService,
        private $outputStream = STDOUT
    ) {}

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    /**
     * Get the command for the task.
     *
     * @return string The command for the task
     */
    public function getCommand(): string
    {
        return 'queue:list';
    }

    /**
     * Get the description for the task.
     *
     * @return string The description for the task
     */
    public function getDescription(): string
    {
        return 'List all queues';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        List all registered queues.

        This task will list all queues that are registered with the queue service.

        Usage:
        framework queue:list
        EOF;
    }

    public function execute(): void
    {
        $queues = $this->queueService->getQueueNames();

        if (empty($queues))
        {
            $this->write('No queues found'.PHP_EOL);

            return;
        }

        $this->write('Available queues:'.PHP_EOL);

        foreach ($queues as $queue)
        {
            $this->write($queue.PHP_EOL);
        }
    }
}
