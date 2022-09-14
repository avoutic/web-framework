<?php
namespace WebFramework\Core;

abstract class SenderCore extends FrameworkCore
{
    public function get_sender_email(): string
    {
        $default_sender = $this->get_config('sender_core.default_sender');
        $this->verify(strlen($default_sender), 'No default sender e-mail address defined');

        return $default_sender;
    }

    static function send_raw(string $to, string $subject, string $message): bool|string
    {
        // Instantiate correct handler
        //
        $handler_class = WF::get_config('sender_core.handler_class');
        WF::verify(strlen($handler_class), 'No handler for sending configured');

        $handler = new $handler_class();
        WF::verify(method_exists($handler, 'send_raw'), 'No raw send handler available');

        return $handler->send_raw_email($to, $subject, $message);
    }

    /**
     * @param array<mixed> $params
     */
    static function send(string $template_name, string $to, array $params = array()): bool|string
    {
        // Instantiate correct handler
        //
        $handler_class = WF::get_config('sender_core.handler_class');
        WF::verify(strlen($handler_class), 'No handler for sending configured');

        $handler = new $handler_class();

        return $handler->dispatch_template_email($template_name, $to, $params);
    }

    /**
     * @param array<mixed> $params
     */
    public function dispatch_template_email(string $template_name, string $to, array $params = array()): bool|string
    {
        WF::verify(method_exists($this, $template_name), 'No template handler available');

        return $this->$template_name($to, $params);
    }

    abstract public function send_raw_email(string $to, string $subject, string $message): bool|string;

    // Functions called by the base framework
    //

    // In User::send_verify_mail()
    /**
     * @param array<mixed> $params
     */
    abstract public function email_verification_link(string $to, array $params): bool|string;

    // In User::send_change_verify_mail()
    /**
     * @param array<mixed> $params
     */
    abstract public function change_email_verification_link(string $to, array $params): bool|string;

    // In User::send_password_reset_mail()
    /**
     * @param array<mixed> $params
     */
    abstract public function password_reset(string $to, array $params): bool|string;

    // In User::send_new_password()
    /**
     * @param array<mixed> $params
     */
    abstract public function new_password(string $to, array $params): bool|string;
};
?>
