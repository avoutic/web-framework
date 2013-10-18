<?
function send_mail_message($msg)
{
    $headers = '';

    foreach($msg->get_mail_headers() as $key => $value)
        $headers .= "$key: $value".PHP_EOL;

    foreach ($msg->get_recipients() as $recipient)
    {
        mail(
            $recipient,
            $msg->get_mail_subject(),
            $msg->get_mail_message(),
            "From: ".$msg->get_sender_name()." <".$msg->get_sender_address().">\n".
            $headers
        );
    }

    return TRUE;
}

function send_mime_mail_message($msg)
{
    $mime_random = sha1(date('r'));
    $msg->add_header('MIME-Version', 1.0);
    $msg->add_header('Content-Type', "multipart/mixed; boundary=\"WFMailMessage-".$mime_random."\"");
    $mail_message = '';

    foreach ($msg->get_mime_parts() as $part)
    {
        $mail_message .=
                "--WFMailMessage-".$mime_random.PHP_EOL;

        foreach ($part['headers'] as $header => $value)
            $mail_message .= "$header: $value".PHP_EOL;

        $mail_message .=
            PHP_EOL.
            $part['content'].
            PHP_EOL;
    }

    $mail_message .=
        "--WFMailMessage-".$mime_random."--".PHP_EOL;

    $msg->set_message($mail_message);

    return dispatch_mail_message($msg);
}
?>
