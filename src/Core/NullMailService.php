<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class NullMailService.
 *
 * A null implementation of the MailService interface that performs no actual mailing operations.
 * Useful for testing or when email functionality is not required.
 */
class NullMailService implements MailService
{
    /**
     * Send a raw email.
     *
     * @param null|string $from      The sender's email address (null to use default)
     * @param string      $recipient The recipient's email address
     * @param string      $title     The email subject
     * @param string      $message   The email body
     *
     * @return bool|string Always returns true
     */
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string
    {
        return true;
    }

    /**
     * Send an email using a template.
     *
     * @param string               $templateId        The ID of the email template to use
     * @param null|string          $from              The sender's email address (null to use default)
     * @param string               $recipient         The recipient's email address
     * @param array<string, mixed> $templateVariables Variables to be used in the template
     *
     * @return bool|string Always returns true
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string
    {
        return true;
    }
}
