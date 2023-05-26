<?php

namespace WebFramework\Core;

class UserMailer
{
    public function __construct(
        protected MailService $mailService,
        protected string $senderEmail,
    ) {
    }

    /**
     * @param array<mixed> $params
     */
    public function emailVerificationLink(string $to, array $params, ?string $templateId = null): bool|string
    {
        $templateId ??= 'email-verification-link';
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
    public function changeEmailVerificationLink(string $to, array $params, ?string $templateId = null): bool|string
    {
        $templateId ??= 'change-email-verification-link';
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
    public function passwordReset(string $to, array $params, ?string $templateId = null): bool|string
    {
        $templateId ??= 'password-reset';
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
    public function newPassword(string $to, array $params, ?string $templateId = null): bool|string
    {
        $templateId ??= 'new-password';
        $username = $params['user']->username;

        $vars = [
            'password' => $params['password'],
            'username' => $username,
        ];

        return $this->mailService->sendTemplateMail($templateId, $this->senderEmail, $to, $vars);
    }
}
