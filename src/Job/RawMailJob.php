<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Job;

use WebFramework\Queue\Job;

/**
 * Job class for queued mail operations.
 */
class RawMailJob implements Job
{
    private string $jobId;

    /**
     * @param null|string $from      The sender's email address (null to use default)
     * @param string      $recipient The recipient's email address
     * @param string      $title     The email subject
     * @param string      $message   The email body
     */
    public function __construct(
        private ?string $from,
        private string $recipient,
        private string $title,
        private string $message,
    ) {}

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function setJobId(string $jobId): void
    {
        $this->jobId = $jobId;
    }

    public function getJobName(): string
    {
        return 'mail:'.$this->recipient.'@'.$this->title;
    }

    /**
     * Get the sender's email address.
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Get the recipient's email address.
     */
    public function getRecipient(): string
    {
        return $this->recipient;
    }

    /**
     * Get the email subject.
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Get the email body.
     */
    public function getMessage(): string
    {
        return $this->message;
    }
}
