<?php

namespace WebFramework\Core;

use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

class PostmarkMailService implements MailService
{
    public function __construct(
        protected PostmarkClientFactory $clientFactory,
        protected string $defaultSender,
        protected string $serverName,
    ) {
    }

    protected function getClient(): PostmarkClient
    {
        return $this->clientFactory->getClient();
    }

    public function sendRawMail(?string $from, string $to, string $subject, string $message): bool
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
            $result = $this->getClient()->sendEmail(
                $from,
                $to,
                $subject,
                null,
                $message
            );
        }
        catch (\GuzzleHttp\Exception\ConnectException $e)
        {
            throw new \RuntimeException('Postmark Connection failure', previous: $e);
        }

        return true;
    }

    /**
     * @param array<mixed> $templateVariables
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
            $templateVariables['server_name'] = $this->serverName;
        }

        try
        {
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
        }
        catch (PostmarkException $e)
        {
            if ($e->postmarkApiErrorCode == 406)
            {
                return 'inactive_address';
            }

            if ($e->postmarkApiErrorCode == 1101)
            {
                throw new \RuntimeException("Template ID {$templateId} not correct");
            }

            throw $e;
        }

        return true;
    }
}
