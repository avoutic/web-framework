Sender Core
===========

Most, if not all, dynamic sites will have to send transactional e-mails on a regular basis. Within web-framework sending transactional e-mails is abstracted in the abstract `SenderCore` class. This class has two important static functions and a default implementation for determining the email to send from:

.. code-block:: php

    abstract class SenderCore {
        function get_sender_email();
        static function send_raw($to, $subject, $message);
        static function send($template_name, $to, $params = array());
    };

By default `get_sender_email()` will return the e-mail address defined in the configuration at `['sender_core']['default_sender']`. But of course you can override this behaviour in your class extension.

You will have to provide an implementation for SenderCore that uses your preferred transactional e-mail system, and you need to link *includes/sender_handler.inc.php* to your implementation.

Postmark implementation
-----------------------

For the base web-framework there is already an implementation for Postmark that can send e.g. e-mail verification mails needed for account registration. This implementation is in *web-framework/includes/sender_postmark.inc.php*. So you could use it by doing:

.. code-block:: shell

    (cd includes && ln -s ../web-framework/includes/sender_postmark.inc.php sender_handler.inc.php)

You also need to tell the configuration to use this class, by adding or modifying to our `$site_config` in *includes/config.php*.

.. code-block:: php

    $site_config = array(
        'sender_core' => array(
            'handler_class' => 'PostmarkSender',
        ),
        'postmark' => array(
            'api_key' => 'THE_KEY_YOU_GET_FROM_POSTMARK',
            'templates' => array(
                'email_verification_link' => 'THE_TEMPLATE_ID',
            ),
        ),
    );

Extending the implementation
----------------------------

But in most cases you'll want to send different e-mails than the standard transactional e-mails that are provided and implemented in the framework itself.

Let's make a small extension that can handle another type of transactional e-mails we want to send. Let's create a file called *includes/sender_postmark_own.inc.php*.

.. code-block:: php

    <?php
    require_once($includes.'sender_postmark.inc.php');

    class PostmarkSenderOwn extends PostmarkSender
    {
        protected function data_mail($to, $params)
        {
            // Template variables expected
            // * data1
            // * data2
            $from = $this->get_sener_email();

            return $this->send_template_email('YOUR_TEMPLATE_ID', $from, $to, $params);
        }
    }
    ?>

To enable it, we'll need to add the following to our `$site_config` array in *includes/config.php*.

.. code-block:: php

    $site_config = array(
        'sender_core' => array(
            'handler_class' => 'PostmarkSenderOwn',
        ),
    );

Now we can send a 'data' email from anywhere in the code by calling:

.. code-block:: php

    function send()
    {
        $params = array(
            'data1' => 'My first data',
            'data2' => 'My seconde data',
        );

        $result = SenderCore::send('data_mail', 'to@unknown.com', $params);
        return $result;
    }
