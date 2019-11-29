<?php
require_once($includes.'sender_core.inc.php');
use Postmark\PostmarkClient;

class PostmarkSender extends SenderCore
{
    function send_raw_email($to, $subject, $message)
    {
        global $global_info;
        $from = $this->get_sender_email();

        verify(isset($global_info['config']['postmark']['api_key']), 'No Postmark API key defined');

        $client = new PostmarkClient($global_info['config']['postmark']['api_key']);

        try
        {
            $result = $client->sendEmail(
                $from,
                $to,
                $subject,
                $message);
        }
        catch (Exception $e)
        {
            return false;
        }

        return true;
    }

    protected function send_template_email($template_id, $from, $to, $template_variables)
    {
        global $global_info;

        verify(isset($global_info['config']['postmark']['api_key']), 'No Postmark API key defined');

        $client = new PostmarkClient($global_info['config']['postmark']['api_key']);

        try
        {
            $result = $client->sendEmailWithTemplate(
                $from,
                $to,
                $template_id,
                $template_variables);
        }
        catch (Exception $e)
        {
            verify($e->postmarkApiErrorCode != 1101, 'Template ID not correct');
            verify(false, 'Unknown Postmark error: '.$e->postmarkApiErrorCode.' - '.$e->message);
        }

        return true;
    }

    function get_template_id($template_name)
    {
        verify(isset($this->config['postmark']['templates'][$template_name]), 'Template mapping not available.');
        return $this->config['postmark']['templates'][$template_name];
    }

    function email_verification_link($to, $params)
    {
        $template_id = $this->get_template_id('email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];

        $vars = array(
            'action_url' => $verify_url,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function change_email_verification_link($to, $params)
    {
        $template_id = $this->get_template_id('change_email_verification_link');
        $from = $this->get_sender_email();
        $verify_url = $params['verify_url'];

        $vars = array(
            'action_url' => $verify_url,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function password_reset($to, $params)
    {
        $template_id = $this->get_template_id('password_reset');
        $from = $this->get_sender_email();
        $reset_url = $params['reset_url'];

        $vars = array(
            'action_url' => $reset_url,
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }

    function new_password($to, $params)
    {
        $template_id = $this->get_template_id('new_password');
        $from = $this->get_sender_email();

        $vars = array(
            'password' => $params['password'],
        );

        return $this->send_template_email($template_id, $from, $to, $vars);
    }
};
?>
