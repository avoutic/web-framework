<?php

namespace WebFramework\Core;

use Exception;
use Postmark\Models\PostmarkException;
use Postmark\PostmarkClient;

class PostmarkSender extends SenderCore
{
    protected function get_api_key(): string
    {
        $api_key_file = $this->get_config('postmark.api_key_file');
        $this->verify(strlen($api_key_file), 'No Postmark API key defined');

        return $this->get_auth_config($api_key_file);
    }

    protected function get_client(): PostmarkClient
    {
        $api_key = $this->get_api_key();

        return new PostmarkClient($api_key);
    }

    public function send_raw_email(string $to, string $subject, string $message): bool
    {
        $from = $this->get_sender_email();
        $client = $this->get_client();

        if (!strlen($to))
        {
            $this->report_error('No recipient e-mail specified. Failing silently');

            return true;
        }

        if (!strlen($from))
        {
            $this->report_error('No source e-mail specified. Failing silently');

            return true;
        }

        try
        {
            $result = $client->sendEmail(
                $from,
                $to,
                $subject,
                null,
                $message
            );
        }
        catch (Exception $e)
        {
            error_log($e->getMessage());

            return false;
        }

        return true;
    }

    /**
     * @param array<mixed> $template_variables
     */
    public function send_template_email(string $template_id, string $from, string $to, array $template_variables): bool|string
    {
        $client = $this->get_client();
        $reply_to = null;

        if (!strlen($to))
        {
            $this->report_error('No recipient e-mail specified. Failing silently');

            return true;
        }

        if (!strlen($from))
        {
            $this->report_error('No source e-mail specified. Failing silently');

            return true;
        }

        if (isset($template_variables['reply_to']))
        {
            $reply_to = $template_variables['reply_to'];
        }

        if (!isset($template_variables['server_name']))
        {
            $template_variables['server_name'] = $this->get_config('server_name');
        }

        try
        {
            $result = $client->sendEmailWithTemplate(
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

            $this->verify($e->postmarkApiErrorCode != 1101, 'Template ID not correct');
            $this->verify(false, 'Unknown Postmark error: '.$e->postmarkApiErrorCode.' - '.$e->getMessage());
        }

        return true;
    }

    protected function get_template_id(string $template_name): string
    {
        $template_name = $this->get_config('postmark.templates.'.$template_name);
        $this->verify(isset($template_name), 'Template mapping not available.');

        return $template_name;
    }

    /**
     * @param array<mixed> $params
     */
    public function email_verification_link(string $to, array $params): bool|string
    {
        $template_id = $this->get_template_id('email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verify_url,
            'username' => $username,
        ];

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function change_email_verification_link(string $to, array $params): bool|string
    {
        $template_id = $this->get_template_id('change_email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $verify_url,
            'username' => $username,
        ];

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function password_reset(string $to, array $params): bool|string
    {
        $template_id = $this->get_template_id('password_reset');
        $from = $this->get_sender_email();
        $reset_url = $params['reset_url'];
        $username = $params['user']->username;

        $vars = [
            'action_url' => $reset_url,
            'username' => $username,
        ];

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    /**
     * @param array<mixed> $params
     */
    public function new_password(string $to, array $params): bool|string
    {
        $template_id = $this->get_template_id('new_password');
        $from = $this->get_sender_email();
        $username = $params['user']->username;

        $vars = [
            'password' => $params['password'],
            'username' => $username,
        ];

        return $this->send_template_email($template_id, $from, $to, $vars);
    }
}
