<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use WebFramework\Job\RawMailJob;
use WebFramework\Job\TemplateMailJob;
use WebFramework\Queue\Queue;

/**
 * Queued mail service implementation.
 *
 * This service queues mail jobs instead of sending them immediately.
 */
class QueuedMailService implements MailService
{
    /**
     * @param Queue $queue The queue service
     */
    public function __construct(
        private Queue $queue,
    ) {}

    /**
     * Send a raw email by queuing it.
     *
     * @param null|string $from      The sender's email address (null to use default)
     * @param string      $recipient The recipient's email address
     * @param string      $title     The email subject
     * @param string      $message   The email body
     *
     * @return bool|string True if queued successfully, or an error message string
     */
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string
    {
        try
        {
            $job = new RawMailJob($from, $recipient, $title, $message);
            $this->queue->dispatch($job);

            return true;
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
    }

    /**
     * Send an email using a template by queuing it.
     *
     * @param string               $templateId        The ID of the email template to use
     * @param null|string          $from              The sender's email address (null to use default)
     * @param string               $recipient         The recipient's email address
     * @param array<string, mixed> $templateVariables Variables to be used in the template
     *
     * @return bool|string True if queued successfully, or an error message string
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string
    {
        try
        {
            $job = new TemplateMailJob($from, $recipient, $templateId, $templateVariables);
            $this->queue->dispatch($job);

            return true;
        }
        catch (\Exception $e)
        {
            return $e->getMessage();
        }
    }
}
