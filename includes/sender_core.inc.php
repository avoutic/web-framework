<?php
abstract class SenderCore extends FrameworkCore
{
    function get_sender_email()
    {
        $default_sender = $this->get_config('sender_core.default_sender');
        WF::verify(strlen($default_sender), 'No default sender e-mail address defined');

        return $default_sender;
    }

    static function send_raw($to, $subject, $message)
    {
        // Instantiate correct handler
        //
        $handler_class = WF::get_config('sender_core.handler_class');
        WF::verify(strlen($handler_class), 'No handler for sending configured');

        $handler = new $handler_class();
        WF::verify(method_exists($handler, 'send_raw'), 'No raw send handler available');

        return $handler->send_raw_email($to, $subject, $message);
    }

    static function send($template_name, $to, $params = array())
    {
        // Instantiate correct handler
        //
        $handler_class = WF::get_config('sender_core.handler_class');
        WF::verify(strlen($handler_class), 'No handler for sending configured');

        $handler = new $handler_class();
        WF::verify(method_exists($handler, $template_name), 'No template handler available');

        return $handler->$template_name($to, $params);
    }

    // Functions called by the base framework
    //

    // In User::send_verify_mail()
    abstract function email_verification_link($to, $params);

    // In User::send_change_verify_mail()
    abstract function change_email_verification_link($to, $params);

    // In User::send_password_reset_mail()
    abstract function password_reset($to, $params);

    // In User::send_new_password()
    abstract function new_password($to, $params);
};

require_once(WF::$site_includes.'sender_handler.inc.php');
?>
