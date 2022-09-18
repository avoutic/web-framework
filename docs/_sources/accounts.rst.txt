Accounts
========

Web-framework implements all the base elements for users and user management. Including support for individual rights, account registration, account verification, changing passwords, changing e-mails, etc.

Core support
------------

The core support is defined in `WebFramework\Core\User`, `WebFramework\Core\Right` and `WebFramework\Core\BaseFactory`. These contain everything needed for creating and retrieving users. Logically, the `User` class extends `DataCore` as it represents a row in the user table, and `BaseFactory` extends `FactoryCore`.

.. note::

       If you have not yet read up on `DataCore` and `FactoryCore`, now would be a good
       moment.

User Registration
-----------------

The base implementation of user registration is implemented in `WebFramework\Actions\RegisterAccount`. This action supports the core user registration for the required fields:

* `email`, which by default is the main identifier for an account.
* `password`
* `accept_terms`
* `username`, is a copy of `email` by default. Can be unique and used to login, if the configuration option in `$config['authenticator']['unique_identifier']` is set to `username`.

The core flow will verify that all relevant values from the registration form are present, then check validity of those fields and register error messages where needed, and if all is correct create a new user account.

If you want to accept and handle other fields, in addition to the fields above, or want to add a handler for things like subscribing to a newsletter, you can extend the core `WebFramework\Actions\RegisterAccount` class and implement one or all of the following functions:

* `custom_get_filter()`
* `custom_prepare_page_content()`
* `custom_value_check()`, should return `true` or `false` to indicate if the performing the actual action is still possible. Returns `true` by default.
* `customer_finalize_create(User $user)`

For example, an extension that would also handle a newsletter subscription checkbox would look like this:

.. code-block:: php

    <?php
    namespace App\Actions;

    use WebFramework\Actions\RegisterAccount;
    use WebFramework\Core\User;

    class RegisterExtra extends RegisterAccount
    {
        static function custom_get_filter(): array
        {
            return array(
                'subscribe' => '0|1',
            );
        }

        function custom_prepare_page_content(): void
        {
            $this->page_content['subscribe'] = $this->get_input_var('subscribe');
        }

        function custom_finalize_create(User $user): void
        {
            $subscribe = $this->get_input_var('subscribe');

            if (!$subscribe)
                return;

            // Add subscription logic here
            //
        }
    };
    ?>

We don't need to implement `custom_value_check()`, because the subscription is not mandatory. But if we wanted it would look something like this:

.. code-block:: php

        function custom_value_check(): bool
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
