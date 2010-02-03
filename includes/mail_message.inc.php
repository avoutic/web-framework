<?php
define('ERROR_INVALID_EMAIL', -1);
define('ERROR_INVALID_NAME', -2);

class MailMessage
{
    protected $sender_address = MAIL_ADDRESS;
    protected $sender_name = SITE_NAME;
    protected $recipients = array();
    protected $mail_subject = '';
    protected $mail_message = '';
    protected $mail_headers = '';

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

    function add_cc($address)
    {
        if (!preg_match('/^\s*'.FORMAT_EMAIL.'\s*$/m', $address))
            return ERROR_INVALID_EMAIL;

        $this->mail_headers .= "CC: $address".PHP_EOL;

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
        foreach ($this->recipients as $recipient)
        {
            mail(
                $recipient,
                $this->mail_subject,
                $this->mail_message,
                "From: ".$this->sender_name." <".$this->sender_address.">\n".
                  $this->mail_headers
            );
        }

        return TRUE;
    }
}

class MimeMailMessage extends MailMessage
{
    protected $mime_parts = array();
    protected $mime_random = '';

    function __construct()
    { 
        $this->mime_random = sha1(date('r'));
        $this->mail_headers .=
            "MIME-Version: 1.0".PHP_EOL.
            "Content-Type: multipart/mixed; boundary=\"WFMailMessage-".$this->mime_random."\"".PHP_EOL;
    }

    function add_mime_part($mime_header, $mime_content)
    {
        $info = array(
            'header' => $mime_header,
            'content' => $mime_content);

        array_push($this->mime_parts, $info);
    }

    function add_text_part($content)
    {
        $this->add_mime_part(
            "Content-Type: text/plain; charset=\"iso-8859-1\"".PHP_EOL."Content-Transfer-Encoding: 7bit".PHP_EOL,
            $content.PHP_EOL);
    }

    function add_html_part($content)
    {
        $this->add_mime_part(
            "Content-Type: text/html; charset=\"iso-8859-1\"".PHP_EOL."Content-Transfer-Encoding: 7bit".PHP_EOL,
            $content.PHP_EOL);
    }

    function add_content_as_attachment($filename, $content)
    {
        $this->add_mime_part(
            "Content-Type: application/octet-stream; name=\"$filename\"".PHP_EOL.
            "Content-Transfer-Encoding: base64".PHP_EOL.
            "Content-Disposition: attachment".PHP_EOL,
            $content.PHP_EOL);
    }

    function add_file_as_attachment($filename, $content_file)
    {
        $content = chunk_split(base64_encode(file_get_contents($content_file)));

        $this->add_content_as_attachment($filename, $content);
    }

    function send()
    {
        foreach ($this->mime_parts as $part)
        {
            $this->mail_message .=
                "--WFMailMessage-".$this->mime_random.PHP_EOL.
                $part['header'].
                PHP_EOL.
                $part['content'].
                PHP_EOL;
        }

        $this->mail_message .=
            "--WFMailMessage-".$this->mime_random."--".PHP_EOL;
        
        return parent::send();
    }
}
?>