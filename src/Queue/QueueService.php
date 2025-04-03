<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Queue;

class QueueService
{
    /** @var array<string, Queue> */
    private array $queues = [];

    private ?Queue $defaultQueue = null;

    /**
     * Register a new queue with the service.
     */
    public function register(string $name, Queue $queue, bool $isDefault = false): void
    {
        if (isset($this->queues[$name]))
        {
            throw new \RuntimeException("Queue '{$name}' is already registered");
        }

        if ($name === 'default')
        {
            throw new \RuntimeException("Queue 'default' is reserved");
        }

        $this->queues[$name] = $queue;

        if ($isDefault)
        {
            $this->defaultQueue = $queue;
        }
    }

    /**
     * Get a registered queue by name.
     *
     * @throws \RuntimeException if queue does not exist
     */
    public function get(string $name): Queue
    {
        if ($name === 'default')
        {
            return $this->getDefaultQueue();
        }

        if (!isset($this->queues[$name]))
        {
            throw new \RuntimeException("Queue '{$name}' does not exist");
        }

        return $this->queues[$name];
    }

    /**
     * Get the default queue.
     *
     * @throws \RuntimeException if default queue is not set
     */
    public function getDefaultQueue(): Queue
    {
        if ($this->defaultQueue === null)
        {
            throw new \RuntimeException('Default queue is not set');
        }

        return $this->defaultQueue;
    }

    /**
     * Dispatch a job to a queue.
     */
    public function dispatch(Job $job, string $queue = 'default', int $delay = 0): void
    {
        $this->get($queue)->dispatch($job, $delay);
    }

    /**
     * Count the number of jobs in a queue.
     */
    public function count(string $queue = 'default'): int
    {
        return $this->get($queue)->count();
    }

    /**
     * Get the next job from a queue.
     */
    public function popJob(string $queue = 'default'): ?Job
    {
        return $this->get($queue)->popJob();
    }

    /**
     * Get all registered queue names.
     *
     * @return array<string>
     */
    public function getQueueNames(): array
    {
        return array_keys($this->queues);
    }

    /**
     * Clear a queue.
     */
    public function clear(string $queue = 'default'): void
    {
        $this->get($queue)->clear();
    }

    /**
     * Clear all queues.
     */
    public function clearAll(): void
    {
        foreach ($this->queues as $queue)
        {
            $queue->clear();
        }
    }
}
