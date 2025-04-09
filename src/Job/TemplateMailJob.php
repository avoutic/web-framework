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
class TemplateMailJob implements Job
{
    /**
     * @param null|string          $from              The sender's email address (null to use default)
     * @param string               $recipient         The recipient's email address
     * @param null|string          $templateId        The ID of the email template to use
     * @param array<string, mixed> $templateVariables Variables to be used in the template
     */
    public function __construct(
        private ?string $from,
        private string $recipient,
        private ?string $templateId = null,
        private array $templateVariables = [],
    ) {}

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
     * Get the template ID.
     */
    public function getTemplateId(): ?string
    {
        return $this->templateId;
    }

    /**
     * Get the template variables.
     *
     * @return array<string, mixed> The template variables
     */
    public function getTemplateVariables(): array
    {
        return $this->templateVariables;
    }
}
