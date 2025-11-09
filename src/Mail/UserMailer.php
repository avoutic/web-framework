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
 * Class UserMailer.
 *
 * Handles sending various types of emails to users.
 */
class UserMailer
{
    /**
     * UserMailer constructor.
     *
     * @param LoggerInterface       $logger            The logger
     * @param MailService           $mailService       The mail service for sending emails
     * @param string                $senderEmail       The default sender email address
     * @param array<string, string> $templateOverrides Template overrides for different email types
     */
    public function __construct(
        private LoggerInterface $logger,
        private MailService $mailService,
        private string $senderEmail,
        private array $templateOverrides,
    ) {}

    /**
     * Send an email verification code to a user.
     *
     * @param string       $to     The recipient's email address
     * @param array<mixed> $params Parameters for the email template
     *
     * @return bool|string True if sent successfully, or an error message string
     */
    public function emailVerificationCode(string $to, array $params): bool|string
    {
        $this->logger->debug('Sending email verification code', ['to' => $to]);

        $templateId = $this->templateOverrides['email-verification-code'] ?? 'email-verification-code';
        $code = $params['code'];
        $username = $params['user']['username'];
        $validity = $params['validity'];

        $vars = [
            'code' => $code,
            'validity' => $validity,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * Send a change email verification code to a user.
     *
     * @param string       $to     The recipient's email address
     * @param array<mixed> $params Parameters for the email template
     *
     * @return bool|string True if sent successfully, or an error message string
     */
    public function changeEmailVerificationCode(string $to, array $params): bool|string
    {
        $this->logger->debug('Sending change email verification code', ['to' => $to]);

        $templateId = $this->templateOverrides['change-email-verification-code'] ?? 'change-email-verification-code';
        $code = $params['code'];
        $username = $params['user']['username'];
        $validity = $params['validity'];

        $vars = [
            'code' => $code,
            'validity' => $validity,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * Send a password reset email to a user.
     *
     * @param string       $to     The recipient's email address
     * @param array<mixed> $params Parameters for the email template
     *
     * @return bool|string True if sent successfully, or an error message string
     */
    public function passwordReset(string $to, array $params): bool|string
    {
        $this->logger->debug('Sending password reset email', ['to' => $to]);

        $templateId = $this->templateOverrides['password-reset'] ?? 'password-reset';
        $resetUrl = $params['reset_url'];
        $username = $params['user']['username'];

        $vars = [
            'action_url' => $resetUrl,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * Send a new password email to a user.
     *
     * @param string       $to     The recipient's email address
     * @param array<mixed> $params Parameters for the email template
     *
     * @return bool|string True if sent successfully, or an error message string
     */
    public function newPassword(string $to, array $params): bool|string
    {
        $this->logger->debug('Sending new password email', ['to' => $to]);

        $templateId = $this->templateOverrides['new-password'] ?? 'new-password';
        $username = $params['user']['username'];

        $vars = [
            'password' => $params['password'],
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }
}
