<?php

namespace WebFramework\Core;

class UserMailer
{
    /**
     * @param array<string, string> $templateOverrides;
     */
    public function __construct(
        protected MailService $mailService,
        protected string $senderEmail,
        protected array $templateOverrides,
    ) {
    }

    /**
     * @param array<mixed> $params
     */
    public function emailVerificationLink(string $to, array $params): bool|string
    {
        $templateId = $this->templateOverrides['email-verification-link'] ?? 'email-verification-link';
        $verifyUrl = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verifyUrl,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function changeEmailVerificationLink(string $to, array $params): bool|string
    {
        $templateId = $this->templateOverrides['change-email-verification-link'] ?? 'change-email-verification-link';
        $verifyUrl = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verifyUrl,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function passwordReset(string $to, array $params): bool|string
    {
        $templateId = $this->templateOverrides['password-reset'] ?? 'password-reset';
        $resetUrl = $params['reset_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $resetUrl,
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function newPassword(string $to, array $params): bool|string
    {
        $templateId = $this->templateOverrides['new-password'] ?? 'new-password';
        $username = $params['user']->username;

        $vars = [
            'password' => $params['password'],
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }
}
