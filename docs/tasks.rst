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

            $user_factory = new UserFactory($this->global_info);
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
