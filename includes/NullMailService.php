<?php

namespace WebFramework\Core;

class NullMailService implements MailService
{
    public function send_raw(string $recipient, string $title, string $message): void
    {
    }
}
