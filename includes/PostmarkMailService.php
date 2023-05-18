<?php

namespace WebFramework\Core;

use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

class PostmarkMailService implements MailService
{
    public function __construct(
        protected PostmarkClientFactory $client_factory,
        protected string $default_sender,
        protected string $server_name,
    ) {
    }

    protected function get_client(): PostmarkClient
    {
        return $this->client_factory->get_client();
    }

    public function send_raw_mail(?string $from, string $to, string $subject, string $message): bool
    {
        $from = $this->default_sender;

        if (!strlen($to))
        {
            throw new \RuntimeException('No recipient e-mail specified');
        }

        if (!strlen($from))
        {
            throw new \RuntimeException('No source e-mail specified');
        }

        $result = $this->get_client()->sendEmail(
            $from,
            $to,
            $subject,
            null,
            $message
        );

        return true;
    }

    /**
     * @param array<mixed> $template_variables
     */
    public function send_template_mail(string $template_id, ?string $from, string $to, array $template_variables): bool|string
    {
        $from = $from ?? $this->default_sender;
        $reply_to = $template_variables['reply_to'] ?? null;

        if (!strlen($to))
        {
            throw new \RuntimeException('No recipient e-mail specified');
        }

        if (!strlen($from))
        {
            throw new \RuntimeException('No source e-mail specified');
        }

        if (!isset($template_variables['server_name']))
        {
            $template_variables['server_name'] = $this->server_name;
        }

        try
        {
            $result = $this->get_client()->sendEmailWithTemplate(
                $from,
                $to,
                $template_id,
                $template_variables,
                true,               // inlineCSS
                null,               // tag
                true,               // trackOpens
                $reply_to           // replyTo
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
                throw new \RuntimeException("Template ID {$template_id} not correct");
            }

            throw $e;
        }

        return true;
    }
}
