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

use WebFramework\Exception\ArgumentParserException;
use WebFramework\Queue\QueueService;

class QueueClear extends ConsoleTask
{
    private ?string $queueName = null;

    /**
     * QueueClear constructor.
     *
     * @param QueueService $queueService The queue service
     * @param resource     $outputStream The output stream
     */
    public function __construct(
        private QueueService $queueService,
        private $outputStream = STDOUT
    ) {}

    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    public function getCommand(): string
    {
        return 'queue:clear';
    }

    public function getDescription(): string
    {
        return 'Clear a task queue';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Clear a task queue.

        This task will remove all jobs from the specified queue.

        Usage:
        framework queue:clear <queueName>
        EOF;
    }

    public function getArguments(): array
    {
        return [
            new TaskArgument('queueName', 'The name of the queue to clear', true, [$this, 'setQueueName']),
        ];
    }

    public function getQueueName(): ?string
    {
        return $this->queueName;
    }

    public function setQueueName(string $queueName): void
    {
        $this->queueName = $queueName;
    }

    public function execute(): void
    {
        if ($this->queueName === null)
        {
            throw new ArgumentParserException('Queue name not set');
        }

        try
        {
            $this->queueService->clear($this->queueName);
            $this->write("Queue '{$this->queueName}' cleared.".PHP_EOL);
        }
        catch (\Exception $e)
        {
            $this->write("Error clearing queue '{$this->queueName}': ".$e->getMessage().PHP_EOL);
        }
    }
}
