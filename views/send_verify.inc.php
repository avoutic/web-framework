<?php
namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageBasic;

class SendVerify extends PageBasic
{
    static function get_filter(): array
    {
        return array(
                'code' => '.*',
                );
    }

    protected function get_title(): string
    {
        return "Request verification mail.";
    }

    protected function do_logic(): void
    {
        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'missing-code');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
            return;

        $this->blacklist_verify($msg['action'] == 'send_verify', 'wrong-action', 2);

        if ($msg['timestamp'] + 86400 < time())
        {
            $login_page = $this->get_base_url().$this->get_config('pages.login.location');

            // Expired
            header("Location: ${login_page}?".$this->get_message_for_url('error', 'Send verification link expired', 'Please login again to request a new one.'));
            exit();
        }

        $base_factory = new BaseFactory();

        // Check user status
        //
        $user = $base_factory->get_user_by_username($msg['username']);

        if ($user !== false && !$user->is_verified())
            $user->send_verify_mail($msg['params']);

        // Redirect to main sceen
        //
        $after_verify_page = $this->get_base_url().$this->get_config('pages.send_verify.after_verify_page');

        header("Location: ${after_verify_page}?".$this->get_message_for_url('success', 'Verification mail sent', 'Verification mail is sent (if not already verified). Please check your mailbox and follow the instructions.'));
        exit();
    }

    protected function display_content(): void
    {
        $this->load_template('send_verify.tpl', $this->page_content);
    }
};
?>
