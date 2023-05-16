<?php

namespace WebFramework\Core;

interface MailService
{
    public function send_raw_mail(?string $from, string $recipient, string $title, string $message): bool|string;

    /**
     * @param array<string, mixed> $template_variables
     */
    public function send_template_mail(string $template_id, ?string $from, string $recipient, array $template_variables): bool|string;
}
