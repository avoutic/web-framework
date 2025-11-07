<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Handler;

use Psr\Log\LoggerInterface;
use WebFramework\Event\EventService;
use WebFramework\Exception\InvalidJobException;
use WebFramework\Job\EventJob;
use WebFramework\Queue\Job;
use WebFramework\Queue\JobHandler;

/**
 * @implements JobHandler<Job>
 */
class EventJobHandler implements JobHandler
{
    public function __construct(
        private EventService $eventService,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param EventJob $job
     */
    public function handle(Job $job): void
    {
        if (!$job instanceof EventJob)
        {
            /** @var class-string $jobClass */
            $jobClass = get_class($job);
            $this->logger->error('EventJobHandler received invalid job type', ['jobClass' => $jobClass]);

            throw new InvalidJobException(EventJob::class, $jobClass);
        }

        $this->logger->debug('Handling EventJob', ['jobId' => $job->getJobId(), 'jobName' => $job->getJobName()]);

        $listener = $this->eventService->getListenerByClass($job->listenerClass);

        $listener->handle($job->event);
    }
}
