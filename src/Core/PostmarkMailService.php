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

use GuzzleHttp\Exception\ConnectException;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

/**
 * Class PostmarkMailService.
 *
 * Implements the MailService interface using the Postmark email service.
 */
class PostmarkMailService implements MailService
{
    /**
     * PostmarkMailService constructor.
     *
     * @param Instrumentation       $instrumentation    The instrumentation service for performance tracking
     * @param PostmarkClientFactory $clientFactory      The factory for creating Postmark client instances
     * @param RuntimeEnvironment    $runtimeEnvironment The runtime environment service
     * @param string                $defaultSender      The default sender email address
     */
    public function __construct(
        private Instrumentation $instrumentation,
        private PostmarkClientFactory $clientFactory,
        private RuntimeEnvironment $runtimeEnvironment,
        private string $defaultSender,
    ) {}

    /**
     * Get the Postmark client instance.
     */
    private function getClient(): PostmarkClient
    {
        return $this->clientFactory->getClient();
    }

    /**
     * Send a raw email.
     *
     * @param null|string $from    The sender's email address (null to use default)
     * @param string      $to      The recipient's email address
     * @param string      $subject The email subject
     * @param string      $message The email body
     *
     * @return bool|string True if sent successfully, or an error message string
     *
     * @throws \RuntimeException If no recipient or sender email is specified
     */
    public function sendRawMail(?string $from, string $to, string $subject, string $message): bool|string
    {
        $from = $this->defaultSender;

        if (!strlen($to))
        {
            throw new \RuntimeException('No recipient e-mail specified');
        }

        if (!strlen($from))
        {
            throw new \RuntimeException('No source e-mail specified');
        }

        try
        {
            $span = $this->instrumentation->startSpan('mail.send_raw');
            $result = $this->getClient()->sendEmail(
                $from,
                $to,
                $subject,
                null,
                $message
            );
            $this->instrumentation->finishSpan($span);
        }
        catch (ConnectException $e)
        {
            throw new \RuntimeException('Postmark Connection failure', previous: $e);
        }

        return true;
    }

    /**
     * Send an email using a template.
     *
     * @param string               $templateId        The ID of the email template to use
     * @param null|string          $from              The sender's email address (null to use default)
     * @param string               $to                The recipient's email address
     * @param array<string, mixed> $templateVariables Variables to be used in the template
     *
     * @return bool|string True if sent successfully, or an error message string
     *
     * @throws \RuntimeException If no recipient or sender email is specified
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $to, array $templateVariables): bool|string
    {
        $from = $from ?? $this->defaultSender;
        $replyTo = $templateVariables['reply_to'] ?? null;

        if (!strlen($to))
        {
            throw new \RuntimeException('No recipient e-mail specified');
        }

        if (!strlen($from))
        {
            throw new \RuntimeException('No source e-mail specified');
        }

        if (!isset($templateVariables['server_name']))
        {
            $templateVariables['server_name'] = $this->runtimeEnvironment->getServerName();
        }

        try
        {
            $span = $this->instrumentation->startSpan('mail.send_template');
            $result = $this->getClient()->sendEmailWithTemplate(
                $from,
                $to,
                $templateId,
                $templateVariables,
                true,               // inlineCSS
                null,               // tag
                true,               // trackOpens
                $replyTo           // replyTo
            );
            $this->instrumentation->finishSpan($span);
        }
        catch (PostmarkException $e)
        {
            if ($e->PostmarkApiErrorCode == 406)
            {
                return 'inactive_address';
            }

            if ($e->PostmarkApiErrorCode == 1101)
            {
                throw new \RuntimeException("Template ID {$templateId} not correct");
            }

            throw $e;
        }

        return true;
    }
}
