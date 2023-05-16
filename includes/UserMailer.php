<?php

namespace WebFramework\Core;

class UserMailer
{
    public function __construct(
        protected MailService $mail_service,
        protected string $sender_email,
    ) {
    }

    /**
     * @param array<mixed> $params
     */
    public function email_verification_link(string $to, array $params, ?string $template_id = null): bool|string
    {
        $template_id ??= 'email-verification-link';
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verify_url,
            'username' => $username,
        ];

        return $this->mail_service->send_template_mail($template_id, $this->sender_email, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function change_email_verification_link(string $to, array $params, ?string $template_id = null): bool|string
    {
        $template_id ??= 'change-email-verification-link';
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verify_url,
            'username' => $username,
        ];

        return $this->mail_service->send_template_mail($template_id, $this->sender_email, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function password_reset(string $to, array $params, ?string $template_id = null): bool|string
    {
        $template_id ??= 'password-reset';
        $reset_url = $params['reset_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $reset_url,
            'username' => $username,
        ];

        return $this->mail_service->send_template_mail($template_id, $this->sender_email, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function new_password(string $to, array $params, ?string $template_id = null): bool|string
    {
        $template_id ??= 'new-password';
        $username = $params['user']->username;

        $vars = [
            'password' => $params['password'],
            'username' => $username,
        ];

        return $this->mail_service->send_template_mail($template_id, $this->sender_email, $to, $vars);
    }
}
