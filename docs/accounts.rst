Accounts
========

Web-framework implements all the base elements for users and user management. Including support for individual rights, account registration, account verification, changing passwords, changing e-mails, etc.

Core support
------------

The core support is defined in *includes/base_logic.inc.php*. This file defines the base `User` and `Right` classes and the `BaseFactory` for creating and retrieving users. Logically, the `User` class extends the `DataCore` as it represents a row in the user table, and ithe `BaseFactory` extends `FactoryCore`.

.. info::

       If you have not yet read up on `DataCore` and `FactoryCore`, now would be a good
       moment.

User Registration
-----------------

The base implementation of user registration is implemented in *views/register_account.inc.php* in the class `PageRegister`. This view supports the core user registration for the required fields:

* `email`, which by default will also function as the username
* `password`
* `accept_terms`
* `username`, but only if the configuration option in `$config['registration']['email_is_username']` is set to false.

The core flow will verify that all relevant values from the registration form are present, then check validity of those fields and register error messages where needed, and if all is correct create a new user account.

If you want to accept and handle other fields, in addition to the fields above, or want to add a handler for things like subscribing to a newsletter, you can extend the core `PageRegister` class and implement one or all of the following functions:

* `custom_get_filter()`
* `custom_prepare_page_content()`
* `custom_value_check()`, should return `true` or `false` to indicate if the performing the actual action is still possible. Returns `true` by default.
* `customer_finalize_create($user)`

For example, an extension that would also handle a newsletter subscription checkbox would look like this:

.. code-block:: php

    <?php
    require_once($views.'register_account.inc.php');

    class PageRegisterExtra extends PageRegister
    {
        static function custom_get_filter()
        {
            return array(
                'subscribe' => '0|1',
            );
        }

        function custom_prepare_page_content()
        {
            $this->page_content['subscribe'] = $this->get_input_var('subscribe');
        }

        function custom_finalize_create($user)
        {
            $subscribe = $this->get_input_var('subscribe');

            if (!$subscribe)
                return;

            // Add subscription logic here
            //
        }
    };
    ?>

We don't need to implement `custom_value_check`, because the subscription is not mandatory. But if we wanted it would look something like this:

.. code-block:: php

        function custom_value_check()
        {
            $success = true;
            $subscribe = $this->get_input_var('subscribe');

            if (!$subscribe)
            {
                $this->add_message('error', 'Not subscribed', 'You have to subscribe to our newsletter.');
                $success = false;
           }

           return $success;
       }
