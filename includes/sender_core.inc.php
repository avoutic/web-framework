<?php
abstract class SenderCore
{
    protected $global_info;
    protected $config;

    function __construct($global_info)
    {
        $this->global_info = $global_info;
        $this->config = $global_info['config'];
    }

    function get_sender_email()
    {
        $default_sender = $this->config['sender_core']['default_sender'];
        verify(strlen($default_sender), 'No default sender e-mail address defined');

        return $default_sender;
    }

    static function send_raw($to, $subject, $message)
    {
        global $global_info, $global_config, $site_includes;

        // Instantiate correct handler
        //
        verify(isset($global_config['sender_core']['handler_class']), 'No handler for sending configured');
        $handler_class = $global_config['sender_core']['handler_class'];

        $handler = new $handler_class($global_info);
        verify(method_exists($handler, 'send_raw'), 'No raw send handler available');

        return $handler->send_raw_email($to, $subject, $message);
    }

    static function send($template_name, $to, $params = array())
    {
        global $global_info, $global_config, $site_includes;

        // Instantiate correct handler
        //
        verify(isset($global_config['sender_core']['handler_class']), 'No handler for sending configured');
        $handler_class = $global_config['sender_core']['handler_class'];

        $handler = new $handler_class($global_info);
        verify(method_exists($handler, $template_name), 'No template handler available');

        return $handler->$template_name($to, $params);
    }

    // Functions called by the base framework
    //

    // In User::send_verify_mail()
    abstract function email_verification_link($to, $params);
};

require_once($site_includes.'sender_handler.inc.php');
?>
