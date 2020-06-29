<?php
abstract class PageContactBase extends PageBasic
{
    abstract protected function get_email_in_text();

    protected static function get_contact_types()
    {
        return array(
            'feature_request' => 'Feature Request',
            'bug' => 'Bug',
            'question' => 'Question',
            'other' => 'Other');
    }

    static function get_filter()
    {
        $contact_types = static::get_contact_types();
        $regex = implode('|', array_keys($contact_types));

        return array(
                'type' => $regex,
                'name' => FORMAT_NAME,
                'email' => FORMAT_EMAIL,
                'subject' => '.*',
                'message_content' => '.*',
                );
    }

    function get_title()
    {
        return 'Contact form';
    }

    function get_description()
    {
        return 'Contact form';
    }

    function get_keywords()
    {
        return 'Contact, Contact form';
    }

    function do_logic()
    {
        $this->page_content['logged_in'] = $this->is_authenticated();
        $this->page_content['name'] = $this->get_input_var('name');
        $this->page_content['email'] = $this->get_input_var('email');
        $this->page_content['type'] = $this->get_input_var('type');
        $this->page_content['subject'] = $this->get_input_var('subject');
        $this->page_content['message_content'] = $this->get_input_var('message_content');
        $this->page_content['contact_types'] = static::get_contact_types();

        // Check if this is a true attempt
        //
        if (!strlen($this->get_input_var('do')))
            return;

        $name = $this->get_input_var('name');
        $email = $this->get_input_var('email');
        $subject = $this->get_input_var('subject');

        if ($this->is_authenticated())
        {
            $name = $this->get_authenticated('username');
            $email = $this->get_authenticated('email');
        }

        // Check if name, email address, subject and message are present
        //
        if (!strlen($name)) {
            $this->add_message('error', 'Please enter a name.', 'Names can contain any letters, numbers, underscores and hyphens.');
            return;
        }

        if (strtr($name, CHAR_FILTER, '0000000000000000') != $name) {
            $this->add_message('error', 'Please enter a correct name.', 'Illegal characters found in name!');
            return;
        }

        if (!strlen($email)) {
            $this->add_message('error', 'Please enter an email address.', 'Email addresses can contain letters, numbers, dots, underscores and hyphens.');
            return;
        }

        if (!strlen($subject)) {
            $this->add_message('error', 'Please enter a subject.', 'Subject can contain any character.');
            return;
        }

        if (strtr($subject, CHAR_FILTER, '0000000000000000') != $subject) {
            $this->add_message('error', 'Please enter a correct subject.', 'Illegal characters found in subject!');
            return;
        }

        if (!strlen($this->get_input_var('message_content'))) {
            $this->add_message('error', 'Please enter a message.', 'Messages can contain any character.');
            return;
        }

        // Send mail to administrator
        //
        $result = SenderCore::raw($this->get_config('sender_core.default_sender'),
                $this->get_config('site_name').": User '".$name."' contacted you about '".$this->get_input_var('type')."' '".$subject."'",
                $this->get_input_var('message_content'),
                "From: ".$email."\r\n");

        // Redirect to main sceen
        //
        if ($result === FALSE)
            header("Location: ?".$this->get_message_for_url('error', 'Message failed to send.', 'Please contact us directly on '.$this->get_email_in_text()));
        else
            header("Location: ?".$this->get_message_for_url('success', 'Message sent successfully.'));
    }

    function display_content()
    {
        $this->load_template('contact.tpl', $this->page_content);
    }
};
?>
