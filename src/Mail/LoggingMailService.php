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

use Psr\Log\LoggerInterface;

/**
 * Class LoggingMailService.
 *
 * An implementation of the MailService and MailBackend interfaces that writes mails to the
 * log instead of sending them. This makes mailed content, such as verification codes,
 * reachable on a development machine without a mail backend.
 *
 * For development only. The logged mails contain secrets, such as verification codes and
 * newly generated passwords. Never bind this service in a production environment.
 */
class LoggingMailService implements MailService, MailBackend
{
    /**
     * LoggingMailService constructor.
     *
     * @param LoggerInterface $logger The logger the mails are written to
     */
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    /**
     * Log a raw email instead of sending it.
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
        $this->logger->info('Mail not sent, logged instead', [
            'from' => $from,
            'recipient' => $recipient,
            'title' => $title,
            'message' => $message,
        ]);

        return true;
    }

    /**
     * Log a template email instead of sending it.
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
        $this->logger->info('Template mail not sent, logged instead', [
            'template_id' => $templateId,
            'from' => $from,
            'recipient' => $recipient,
            'template_variables' => $templateVariables,
        ]);

        return true;
    }
}
