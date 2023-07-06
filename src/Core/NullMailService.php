<?php

namespace WebFramework\Core;

class NullMailService implements MailService
{
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string
    {
        return true;
    }

    /**
     * @param array<string, mixed> $templateVariables
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string
    {
        return true;
    }
}
