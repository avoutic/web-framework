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
use WebFramework\Exception\JobDataException;
use WebFramework\Exception\JobExecutionException;
use WebFramework\Job\TemplateMailJob;
use WebFramework\Mail\MailBackend;
use WebFramework\Queue\Job;
use WebFramework\Queue\JobHandler;

/**
 * @implements JobHandler<Job>
 */
class TemplateMailJobHandler implements JobHandler
{
    public function __construct(
        private MailBackend $mailBackend,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param TemplateMailJob $job
     */
    public function handle(Job $job): void
    {
        if (!$job instanceof TemplateMailJob)
        {
            /** @var class-string $jobClass */
            $jobClass = get_class($job);
            $this->logger->error('TemplateMailJobHandler received invalid job type', ['jobClass' => $jobClass]);

            throw new InvalidJobException(TemplateMailJob::class, $jobClass);
        }

        $this->logger->debug('Handling TemplateMailJob', [
            'jobId' => $job->getJobId(),
            'jobName' => $job->getJobName(),
            'templateId' => $job->getTemplateId(),
            'recipient' => $job->getRecipient(),
        ]);

        $templateId = $job->getTemplateId();
        if ($templateId === null)
        {
            $this->logger->error('TemplateMailJob missing templateId', [
                'jobId' => $job->getJobId(),
            ]);

            throw new JobDataException($job->getJobName(), 'templateId');
        }

        $result = $this->mailBackend->sendTemplateMail(
            $templateId,
            $job->getFrom(),
            $job->getRecipient(),
            $job->getTemplateVariables(),
        );

        if ($result !== true)
        {
            $errorMessage = is_string($result) ? $result : 'Unknown error';
            $this->logger->error('Failed to send template mail', [
                'jobId' => $job->getJobId(),
                'templateId' => $job->getTemplateId(),
                'error' => $errorMessage,
            ]);

            throw new JobExecutionException($job->getJobName(), $errorMessage);
        }
    }
}
