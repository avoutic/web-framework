<?php

namespace WebFramework\Core;

interface MailService
{
    public function sendRawMail(?string $from, string $recipient, string $title, string $message): bool|string;

    /**
     * @param array<string, mixed> $templateVariables
     */
    public function sendTemplateMail(string $templateId, ?string $from, string $recipient, array $templateVariables): bool|string;
}
