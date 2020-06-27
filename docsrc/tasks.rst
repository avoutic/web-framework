Tasks
=====

Creating a REST API
-------------------

A REST API consists of:

* a view (extending *PageService*)
* One or more routes to the API endpoints

A simple API view:

.. code-block:: php

    <?php
    class UserApi extends PageService
    {
        static function get_permissions()
        {
            return array(
                'logged_in'
            );
        }

        static function get_filter()
        {
            return array(
                'user_id' => FORMAT_ID,
            );
        }

        function get_user()
        {
            $user_id = 0;

            if (!$this->check_required($user_id, 'user_id')) return;

            $user_factory = new UserFactory();
            $user = $user_factory->get_user($user_id);

            $this->output_json(true, $user->username);
        }
    };
    ?>

In order to redirect the GET requests for */api/users/{user_id}* to *get_user()*, we add a route into the route array (*includes/site_logic.inc.php*):

.. code-block:: php

    <?php
    function register_routes()
    {
        register_route('GET /api/users/(\d+)', 'users', 'UsersApi.get_user', array('user_id'));
    }
    ?>

.. tip::

       Place each conceptual part of the API in its own file. So put all functions around
       people in *people.inc.php* and all functions around tasks in *tasks.inc.php*.

Handling POST data in a page
----------------------------

Pages that include a HTML form, button, or other interaction will not always interact via a REST API. The more basic approach is to post the data to the current View and then handle it there. Let's assume we want to update a single field called *name* from our User's page. The View is stored in *views/show_user.inc.php*, and the template in *templates/show_user.tpl.inc.php*.

In our Template for this page we'll need to add a form to handle the interaction. A very basic form looks like this:

.. code-block:: html

    <form method="POST" action="/show-user">
      <input type="hidden" name="do" value="yes"/>
      <input type="hidden" name="token" value="<?=get_csrf_token()?>"/>
      <input type="text" name="name" placeholder="Name" required autofocus autocomplete="off">
      <button type="submit">Change</button>
    </form>

.. important::

   Notice that there are two hidden fields in this form. The *do* variable is to indicate to the logic in the View that this is an actual attempt of submitting the form. (The *do* variable is one of the few variables that is allowed by default in the framework, but only with the value of 'yes').

   The *token* variable contains our CSRF token. Without it, or if a user waits too long, the form will not be accepted. Just as the *do* variable, *token* is allowed by default.

By default all requests are blocked unless we explicitly allow them in the registered routes. In *includes/site_logic.inc.php* we'll have to register a new route in `register_routes()`:

.. code-block:: php

    register_route('POST /show-user', 'show_user', 'PageShowUser.html_main');

.. note::

   The arguments for `register_route()` are:

   1. A Request regex
   2. The name of the view file (without '.inc.php')
   3. The class and function name to trigger

Now we'll need to add the handling in the View as well.

In your View class we'll need to make sure that the *name* variable is allowed to be seen in the code. We'll add it to the filter:

.. code-block:: php

    static function get_filter()
    {
        return array(
                'name' => '[\w \-]+',
        );
    }

In our case we'll only allow names that consist of roman letters, a space and a hyphen.

Now we can use this value in the View's logic:

.. code-block:: php

    function do_logic()
    {
        if (!strlen($this->get_var('do')))
            return;

        $error = false;
        $name = $this->get_var('name');

        if (!strlen($name))
        {
            $error = true;
            $this->add_message('error', 'Name is missing or invalid');
        }

        if ($error)
            return;

        // Actually change the name of user 1
        //
        $user = new User(1);
        $user->update_field('name', $name);

        $this->add_message('success', 'Name changed', 'The name has been changed.');
    }

.. note::

   It's not very clean to change the name of an object directly from the outside. But for purpose of this example, this will do. Idieally you would add a function to the User object to change the name. This reduces coupling of the code.


