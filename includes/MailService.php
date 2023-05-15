<?php

namespace WebFramework\Core;

interface MailService
{
    public function send_raw(string $recipient, string $title, string $message): void;
}
