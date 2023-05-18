<?php

namespace WebFramework\Actions;

use WebFramework\Core\PageAction;
use WebFramework\Core\User;

class ChangeEmail extends PageAction
{
    public static function get_filter(): array
    {
        return [
            'email' => FORMAT_EMAIL,
        ];
    }

    public static function get_permissions(): array
    {
        return [
            'logged_in',
        ];
    }

    protected function get_title(): string
    {
        return 'Change email address';
    }

    // Can be overriden for project specific user factories and user classes
    //
    protected function get_user(string $username): User|false
    {
        $factory = $this->framework->get_base_factory();

        return $factory->get_user_by_username($username);
    }

    protected function do_logic(): void
    {
        $email = $this->get_input_var('email');
        $this->page_content['email'] = $this->get_raw_input_var('email');

        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
        {
            return;
        }

        // Check if email address is present
        //
        if (!strlen($email))
        {
            $this->add_message('error', 'Please enter a correct e-mail address.', 'E-mail addresses can contain letters, digits, hyphens, underscores, dots and at\'s.');

            return;
        }

        // Change email
        //
        $user = $this->get_authenticated_user();
        $old_email = $user->email;

        // Send verification mail
        //
        $result = $user->send_change_email_verify($email);

        if ($result == User::ERR_DUPLICATE_EMAIL)
        {
            $this->add_message('error', 'E-mail address is already in use in another account.', 'The e-mail address is already in use and cannot be re-used in this account. Please choose another address.');

            return;
        }

        if ($result != User::RESULT_SUCCESS)
        {
            $this->add_message('error', 'Unknown errorcode: \''.$result."'", 'Please inform the administrator.');

            return;
        }

        // Redirect to verification request screen
        //
        $return_page = $this->get_base_url().$this->get_config('actions.change_email.return_page');
        header("Location: {$return_page}?".$this->get_message_for_url('success', 'Verification mail has been sent.', 'A verification mail has been sent. Please wait for the e-mail in your inbox and follow the instructions.'));

        exit();
    }

    protected function display_content(): void
    {
        $this->load_template('change_email.tpl', $this->page_content);
    }
}
