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
        $contact_types = PageContactBase::get_contact_types();
        $regex = implode('|', array_keys($contact_types));

        return array(
                'type' => $regex,
                'name' => FORMAT_NAME,
                'email' => FORMAT_EMAIL,
                'subject' => '.*',
                'message_content' => '.*',
                'do' => 'yes'
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
        $this->page_content['logged_in'] = $this->state['logged_in'];
        $this->page_content['name'] = $this->state['input']['name'];
        $this->page_content['email'] = $this->state['input']['email'];
        $this->page_content['type'] = $this->state['input']['type'];
        $this->page_content['subject'] = $this->state['input']['subject'];
        $this->page_content['message_content'] = $this->state['input']['message_content'];

        // Check if this is a true attempt
        //
        if (!strlen($this->state['input']['do']))
            return;

        $name = $this->state['input']['name'];
        $email = $this->state['input']['email'];

        if ($this->state['logged_in'])
        {
            $name = $this->state['username'];
            $email = $this->state['email'];
        }

        // Check if name, email address, subject and message are present
        //
        if (!strlen($name)) {
            $this->add_message('error', 'Please enter a name.', 'Names can contain any letters, numbers, underscores and hyphens.');
            return;
        }

        if (!strlen($email)) {
            $this->add_message('error', 'Please enter an email address.', 'Email addresses can contain letters, numbers, dots, underscores and hyphens.');
            return;
        }

        if (!strlen($this->state['input']['subject'])) {
            $this->add_message('error', 'Please enter a subject.', 'Subject can contain any character.');
            return;
        }

        if (!strlen($this->state['input']['message_content'])) {
            $this->add_message('error', 'Please enter a message.', 'Messages can contain any character.');
            return;
        }

        // Send mail to administrator
        //
        $result = mail(MAIL_ADDRESS,
                SITE_NAME.": User '".$name."' contacted you about '".$this->state['input']['type']."' '".$this->state['input']['subject']."'",
                $this->state['input']['message_content'],
                "From: ".$email."\r\n");

        // Redirect to main sceen
        //
        if ($result === FALSE)
            header("Location: ?mtype=error&message=".urlencode('Message failed to send.')."&extra_message=".urlencode('Please contact us directly on '.$this->get_email_in_text()));
        else
            header("Location: ?mtype=success&message=".urlencode('Message sent successfully.'));
    }

    function display_content()
    {
?>
<form method="post" class="contactform" action="/contact" enctype="multipart/form-data">
<?
	    if (!$this->page_content['logged_in'])
    	{
?>
	<fieldset class="contact_details">
		<legend>Contact Details</legend>
		<p>
			<label class="left" for="name">Name</label>
			<input type="text" class="field" id="name" name="name" value="<?=htmlentities($this->page_content['name']);?>"/>
		</p>
		<p>
			<label class="left" for="email">Email</label>
			<input type="text" class="field" id="email" name="email" value="<?=htmlentities($this->page_content['email']);?>"/>
		</p>
	</fieldset>
<?
	    }
?>
	<fieldset class="message_content">
        <input type="hidden" name="do" value="yes"/>
		<legend>Message Details</legend>
		<p>
			<label class="left" for="type">Type</label>
			<select class="select" id="type" name="type">
<?
        $contact_types = PageContactBase::get_contact_types();

        foreach ($contact_types as $key => $text)
        {
?>
			<option value="<?=$key?>" <?if ($this->page_content['type'] == $key) print("selected");?>><?=$text?></option>
<?
        }
?>
			</select>
		</p>
		<p>
			<label class="left" for="subject">Subject</label> <input type="text" class="field" id="subject" name="subject" value="<?=htmlentities($this->page_content['subject']);?>"/>
		</p>
		<p>
			<label class="left" for="message_content">Message</label> <textarea class="field" id="message_content" name="message_content" rows="20" cols="50"><?=htmlentities($this->page_content['message_content']);?></textarea>
		</p>
	</fieldset>
	<p>
		<label>&nbsp;</label> <input type="submit" class="button" id="submit" value="Submit" />
	</p>
</form>
<?
    }
};
?>
