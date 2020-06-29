<?php
require_once(WF::$includes.'sender_core.inc.php');
use Postmark\PostmarkClient;

class PostmarkSender extends SenderCore
{
    function get_api_key()
    {
        $api_key_file = $this->get_config('postmark.api_key_file');
        WF::verify(strlen($api_key_file), 'No Postmark API key defined');
        $api_key = $this->get_auth_config($api_key_file);

        return $api_key;
    }

    function get_client()
    {
        $api_key = $this->get_api_key();

        $client = new PostmarkClient($api_key);

        return $client;
    }

    function send_raw_email($to, $subject, $message)
    {
        $from = $this->get_sender_email();
        $client = $this->get_client();

        try
        {
            $result = $client->sendEmail(
                $from,
                $to,
                $subject,
                NULL,
                $message);
        }
        catch (Exception $e)
        {
            return false;
        }

        return true;
    }

    function send_template_email($template_id, $from, $to, $template_variables)
    {
        $client = $this->get_client();
        $reply_to = NULL;

        if (isset($template_variables['reply_to']))
            $reply_to = $template_variables['reply_to'];

        try
        {
            $result = $client->sendEmailWithTemplate(
                $from,
                $to,
                $template_id,
                $template_variables,
                true,               // inlineCSS
                NULL,               // tag
                true,               // trackOpens
                $reply_to           // replyTo
            );
        }
        catch (Exception $e)
        {
            if ($e->postmarkApiErrorCode == 406)
                return 'inactive_address';

            WF::verify($e->postmarkApiErrorCode != 1101, 'Template ID not correct');
            WF::verify(false, 'Unknown Postmark error: '.$e->postmarkApiErrorCode.' - '.$e->getMessage());
        }

        return true;
    }

    function get_template_id($template_name)
    {
        $template_name = $this->get_config('postmark.templates.'.$template_name);
        WF::verify(isset($template_name), 'Template mapping not available.');
        return $template_name;
    }

    function email_verification_link($to, $params)
    {
        $template_id = $this->get_template_id('email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = array(
            'action_url' => $verify_url,
            'username' => $username,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function change_email_verification_link($to, $params)
    {
        $template_id = $this->get_template_id('change_email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];
        $username = $params['user']->username;

        $vars = array(
            'action_url' => $verify_url,
            'username' => $username,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function password_reset($to, $params)
    {
        $template_id = $this->get_template_id('password_reset');
        $from = $this->get_sender_email();
        $reset_url = $params['reset_url'];
        $username = $params['user']->username;

        $vars = array(
            'action_url' => $reset_url,
            'username' => $username,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function new_password($to, $params)
    {
        $template_id = $this->get_template_id('new_password');
        $from = $this->get_sender_email();
        $username = $params['user']->username;

        $vars = array(
            'password' => $params['password'],
            'username' => $username,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }
};
?>
