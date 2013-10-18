<?php
define('ERROR_INVALID_EMAIL', -1);
define('ERROR_INVALID_NAME', -2);

function dispatch_mail_message($msg)
{
    global $global_config, $includes, $site_includes;

    $include_file = $global_config['dispatch_mail_include'];
    require_once($include_file);

    return send_mail_message($msg);
}

function dispatch_mime_mail_message($msg)
{
    global $global_config, $includes, $site_includes;

    $include_file = $global_config['dispatch_mail_include'];
    require_once($include_file);

    return send_mime_mail_message($msg);
}

class MailMessage
{
    protected $sender_address = MAIL_ADDRESS;
    protected $sender_name = SITE_NAME;
    protected $recipients = array();
    protected $mail_subject = '';
    protected $mail_message = '';
    protected $mail_headers = '';

    function __construct()
    { 
    }

    function get_sender_address()
    {
        return $this->sender_address;
    }

    function get_sender_name()
    {
        return $this->sender_name;
    }

    function get_recipients()
    {
        return $this->recipients;
    }

    function get_mail_subject()
    {
        return $this->mail_subject;
    }

    function get_mail_message()
    {
        return $this->mail_message;
    }

    function get_mail_headers()
    {
        return $this->mail_headers;
    }

    function add_header($key, $value)
    {
        $this->mail_headers[$key] = $value;
    }

    function set_sender($address, $name)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        if (!preg_match('/^\s*'.FORMAT_NAME.'\s*$/m', $name))
            return ERROR_INVALID_NAME;

        $this->sender_address = $address;
        $this->sender_name = $name;

        return TRUE;
    }

    function add_recipient($address)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        array_push($this->recipients, $address);

        return TRUE;
    }

    function set_recipient($address)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        $this->recipients = array($address);

        return TRUE;
    }

    function add_cc($address)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        $this->add_header('CC', $address);

        return TRUE;
    }

    function add_bcc($address)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        $this->add_header('BCC', $address);

        return TRUE;
    }

    function set_subject($subject)
    {
        $this->mail_subject = $subject;

        return TRUE;
    }

    function set_message($message)
    {
        $this->mail_message = $message;
    }

    function send()
    {
        return dispatch_mail_message($this);
    }
}

class MimeMailMessage extends MailMessage
{
    protected $mime_parts = array();

    function __construct()
    { 
    }

    function get_mime_parts()
    {
        return $this->mime_parts;
    }

    function add_mime_part($mime_type, $mime_name, $mime_header, $mime_content)
    {
        $info = array(
            'type' => $mime_type,
            'name' => $mime_name,
            'headers' => $mime_header,
            'content' => $mime_content);

        array_push($this->mime_parts, $info);
    }

    function add_text_part($content)
    {
        $this->add_mime_part(
            "text/plain",
            "",
            array("Content-Type" => "text/plain; charset=\"iso-8859-1\"",
                  "Content-Transfer-Encoding" => "7bit"),
            $content.PHP_EOL);
    }

    function add_html_part($content)
    {
        $this->add_mime_part(
            "text/html",
            "",
            array("Content-Type" => "text/html; charset=\"iso-8859-1\"",
                  "Content-Transfer-Encoding" => "7bit"),
            $content.PHP_EOL);
    }

    function add_content_as_attachment($filename, $content)
    {
        $this->add_mime_part(
            "application/octet-stream",
            $filename,
            array(
                "Content-Type" => "application/octet-stream; name=\"$filename\"",
                "Content-Transfer-Encoding" => "base64",
                "Content-Disposition" => "attachment"),
            $content.PHP_EOL);
    }

    function add_file_as_attachment($filename, $content_file)
    {
        $content = chunk_split(base64_encode(file_get_contents($content_file)));

        $this->add_content_as_attachment($filename, $content);
    }

    function send()
    {
        return dispatch_mime_mail_message($this);
    }
}

class VerifyMail extends MailMessage
{
    function __construct($name, $username, $hash)
    {
        global $global_config;

        parent::__construct();

        $this->mail_subject = SITE_NAME." account verification mail";
        $this->mail_message = "
Dear $name,

You successfully created your account or changed important information for ".SITE_NAME.". In order to verify the account, please go to the following web location by either clicking the link or manually entering the address into your webbrowser.

To verify the account go to:
http://".$global_config['server_name']."/verify?username=$username&code=$hash

Best regards,
".MAIL_FOOTER;
    }
};

class ForgotPasswordMail extends MailMessage
{
    function __construct($name, $username, $new_pw)
    {
        global $global_config;

        parent::__construct();

        $this->mail_subject = SITE_NAME." password reset mail";
        $this->mail_message = "
Dear $name,

We successfully reset the password for your account '$username' for ".SITE_NAME.".

Your new password is: $new_pw

We advise you to login and change your password as soon as possible.

To login and change your password. please go to:
http://".$global_config['server_name']."/change-password

Best regards,
".MAIL_FOOTER;
    }
};

