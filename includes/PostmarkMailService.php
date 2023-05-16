<?php

namespace WebFramework\Core;

use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

class PostmarkMailService implements MailService
{
    public function __construct(
        protected AssertService $assert_service,
        protected PostmarkClient $client,
        protected string $default_sender,
        protected string $server_name,
    ) {
    }

    public function send_raw_mail(?string $from, string $to, string $subject, string $message): bool
    {
        $from = $this->default_sender;

        if (!strlen($to))
        {
            $this->assert_service->report_error('No recipient e-mail specified. Failing silently');

            return true;
        }

        if (!strlen($from))
        {
            $this->assert_service->report_error('No source e-mail specified. Failing silently');

            return true;
        }

        try
        {
            $result = $this->client->sendEmail(
                $from,
                $to,
                $subject,
                null,
                $message
            );
        }
        catch (\Exception $e)
        {
            $this->assert_service->report_error($e->getMessage());

            return false;
        }

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
            $this->assert_service->report_error('No recipient e-mail specified. Failing silently');

            return true;
        }

        if (!isset($template_variables['server_name']))
        {
            $template_variables['server_name'] = $this->server_name;
        }

        try
        {
            $result = $this->client->sendEmailWithTemplate(
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
