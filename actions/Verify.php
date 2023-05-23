<?php

namespace WebFramework\Actions;

use WebFramework\Core\BaseFactory;
use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class Verify extends PageAction
{
    public static function get_filter(): array
    {
        return [
            'code' => '.*',
        ];
    }

    protected function get_title(): string
    {
        return 'Mail address verification';
    }

    /**
     * @param array<mixed> $data
     */
    protected function custom_after_verify_actions(User $user, array $data): void
    {
    }

    protected function do_logic(): void
    {
        $login_page = $this->get_base_url().$this->get_config('actions.login.location');

        // Check if code is present
        //
        $code = $this->get_input_var('code');
        $this->blacklist_verify(strlen($code), 'missing-code');

        $msg = $this->decode_and_verify_array($code);
        if (!$msg)
        {
            header("Location: {$login_page}?".$this->get_message_for_url('error', 'Verification mail expired', 'Please <a href="'.$login_page.'">request a new one</a> after logging in.'));

            exit();
        }

        $this->blacklist_verify($msg['action'] == 'verify', 'wrong-action', 2);

        if ($msg['timestamp'] + 86400 < time())
        {
            // Expired
            header("Location: {$login_page}?".$this->get_message_for_url('error', 'Verification mail expired', 'Please <a href="'.$login_page.'">request a new one</a> after logging in.'));

            exit();
        }

        $base_factory = $this->container->get(BaseFactory::class);

        // Check user status
        //
        $user = $base_factory->get_user_by_username($msg['username']);

        if ($user === false)
        {
            return;
        }

        if (!$user->is_verified())
        {
            $user->set_verified();
            $this->custom_after_verify_actions($user, $msg['params']);
        }

        // Redirect to main sceen
        //
        $after_verify_page = $this->get_config('actions.login.after_verify_page');
        header("Location: {$login_page}?".$this->get_message_for_url('success', 'Verification succeeded', 'Verification succeeded. You can now use your account.').'&return_page='.urlencode($after_verify_page));

        exit();
    }
}
