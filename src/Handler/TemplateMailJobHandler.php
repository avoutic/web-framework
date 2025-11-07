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
    public function handle(Job $job): bool
    {
        if (!$job instanceof TemplateMailJob)
        {
            $this->logger->error('TemplateMailJobHandler received invalid job type', ['jobClass' => get_class($job)]);

            return false;
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

            return false;
        }

        $result = $this->mailBackend->sendTemplateMail(
            $templateId,
            $job->getFrom(),
            $job->getRecipient(),
            $job->getTemplateVariables(),
        );

        if ($result !== true)
        {
            $this->logger->error('Failed to send template mail', [
                'jobId' => $job->getJobId(),
                'templateId' => $job->getTemplateId(),
                'error' => $result,
            ]);

            return false;
        }

        return true;
    }
}
