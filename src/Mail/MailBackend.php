<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Mail;

use WebFramework\Exception\MailException;

/**
 * Interface MailBackend.
 *
 * Defines the contract for mail backend implementations that actually send emails.
 * This interface is used by queue handlers to send emails, separate from MailService
 * which may queue emails instead of sending them directly.
 */
interface MailBackend
{
    /**
     * Result for a recipient the backend permanently refuses to send to, for example
     * after a hard bounce or a spam complaint.
     */
    public const INACTIVE_ADDRESS = 'inactive_address';

    /**
     * Send a raw email.
     *
     * @param null|string $from      The sender's email address (null to use default)
     * @param string      $recipient The recipient's email address
     * @param string      $title     The email subject
     * @param string      $message   The email body
     *
     * @return bool|string True if sent successfully, or an error message string. The string
     *                     MailBackend::INACTIVE_ADDRESS means the backend permanently refuses
     *                     the recipient, so sending again is pointless
     *
     * @throws MailException If the mail backend fails
     */
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string;

    /**
     * Send an email using a template.
     *
     * @param string               $templateId        The ID of the email template to use
     * @param null|string          $from              The sender's email address (null to use default)
     * @param string               $recipient         The recipient's email address
     * @param array<string, mixed> $templateVariables Variables to be used in the template
     *
     * @return bool|string True if sent successfully, or an error message string. The string
     *                     MailBackend::INACTIVE_ADDRESS means the backend permanently refuses
     *                     the recipient, so sending again is pointless
     *
     * @throws MailException If the mail backend fails
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string;
}
