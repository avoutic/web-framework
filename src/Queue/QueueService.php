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

use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;
use WebFramework\Support\UuidProvider;

class QueueService
{
    /** @var array<string, Queue> */
    private array $queues = [];

    private ?Queue $defaultQueue = null;

    /** @var array<string, string> */
    private array $jobHandlers = [];

    public function __construct(
        private Container $container,
        private LoggerInterface $logger,
        private UuidProvider $uuidProvider,
    ) {}

    /**
     * Register a new queue with the service.
     */
    public function register(string $name, Queue $queue, bool $isDefault = false): void
    {
        $this->logger->debug('Registering queue', ['queue' => $name, 'isDefault' => $isDefault]);

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
     *
     * @param Job    $job         Job to dispatch
     * @param string $queue       Queue name (default: 'default')
     * @param int    $delay       Delay in seconds (default: 0)
     * @param int    $maxAttempts Maximum retry attempts (default: 3)
     */
    public function dispatch(Job $job, string $queue = 'default', int $delay = 0, int $maxAttempts = 3): void
    {
        $job->setJobId($this->uuidProvider->generate());

        $this->get($queue)->dispatch($job, $delay, $maxAttempts);
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

    /**
     * Register a job handler for a job.
     *
     * @param class-string<Job>             $jobClass
     * @param class-string<JobHandler<Job>> $jobHandlerClass
     */
    public function registerJobHandler(string $jobClass, string $jobHandlerClass): void
    {
        $this->logger->debug('Registering job handler', ['jobClass' => $jobClass, 'jobHandlerClass' => $jobHandlerClass]);

        if (isset($this->jobHandlers[$jobClass]))
        {
            throw new \RuntimeException("Handler for '{$jobClass}' is already registered");
        }

        $this->jobHandlers[$jobClass] = $jobHandlerClass;
    }

    /**
     * Get a job handler for a job.
     *
     * @return JobHandler<Job>
     */
    public function getJobHandler(Job $job): JobHandler
    {
        $jobClass = get_class($job);

        if (!isset($this->jobHandlers[$jobClass]))
        {
            throw new \RuntimeException("No handler registered for '{$jobClass}'");
        }

        $jobHandlerClass = $this->jobHandlers[$jobClass];

        $jobHandler = $this->container->get($jobHandlerClass);

        if (!$jobHandler instanceof JobHandler)
        {
            throw new \RuntimeException("Handler for '{$jobClass}' is not a valid job handler");
        }

        return $jobHandler;
    }
}
