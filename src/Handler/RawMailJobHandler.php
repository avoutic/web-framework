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
use WebFramework\Exception\InvalidJobException;
use WebFramework\Exception\JobExecutionException;
use WebFramework\Job\RawMailJob;
use WebFramework\Mail\MailBackend;
use WebFramework\Queue\Job;
use WebFramework\Queue\JobHandler;

/**
 * @implements JobHandler<Job>
 */
class RawMailJobHandler implements JobHandler
{
    public function __construct(
        private MailBackend $mailBackend,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param RawMailJob $job
     */
    public function handle(Job $job): void
    {
        if (!$job instanceof RawMailJob)
        {
            /** @var class-string $jobClass */
            $jobClass = get_class($job);
            $this->logger->error('RawMailJobHandler received invalid job type', ['jobClass' => $jobClass]);

            throw new InvalidJobException(RawMailJob::class, $jobClass);
        }

        $this->logger->debug('Handling RawMailJob', [
            'jobId' => $job->getJobId(),
            'jobName' => $job->getJobName(),
            'recipient' => $job->getRecipient(),
            'title' => $job->getTitle(),
        ]);

        $result = $this->mailBackend->sendRawMail(
            $job->getFrom(),
            $job->getRecipient(),
            $job->getTitle(),
            $job->getMessage(),
        );

        if ($result !== true)
        {
            $errorMessage = is_string($result) ? $result : 'Unknown error';
            $this->logger->error('Failed to send raw mail', [
                'jobId' => $job->getJobId(),
                'error' => $errorMessage,
            ]);

            throw new JobExecutionException($job->getJobName(), $errorMessage);
        }
    }
}
