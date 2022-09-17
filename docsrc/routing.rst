Routing
=======

If nothing is configured, web-framework will not allow any traffic. To make sure pages get handled we have to populate the routing array.

Routes are created by calling functions on the central Framework object. Convention is to create a central file *includes/site\_logic.inc.php* that contains all your routes. This file is then the central switchroom of your application.

Adding a redirect
-----------------

A redirect is an instruction to return a 301 (or other redirect code) to the client and move the communication somewhere else.

In it's most basic form, a redirect is nothing more than a statement to redirect a method and URL to another URL, like this:

.. code-block:: php

    $framework->register_redirect('GET /old_page', '/new-page');

For a more dynamic redirect, we can also include regex-mapping to our redirect. In the third parameter to `register_redirect()` we tell it what kind of return code should be used. In the fourth parameter we provide a mapping from a name to the index of the regex matches. In this case we map the first match by the regex to the name `slug`.

.. code-block:: php

    $framework->register_redirect('GET /old_category/(\w+)', '/new-category/{slug}',
                          301, array('slug' => 1));

Adding a route
--------------

A route is a mapping that will result in a specific action being called and executed.

Let's start with a simple mapping to tell web-framework to send all requests for */dashboard* to your action in *actions/Dashboard.php* with class name `\App\Actions\Dashboard` and to call `html_main()` on that object. That would look something like:

.. code-block:: php

    $framework->register_route('GET /dashboard', '', 'Dashboard.html_main');

In some cases the URL already contains relevant information for your action to use as input. In that case we can map regex-matches to input variables (that are filtered with your action's static `get_filter()` function.

.. code-block:: php

    $this->register_route('GET /product/(\w+)', '', 'Product.html_main', array('slug'));

This will map part of the URL to the `slug` input variable for your Product action.

Handling 404s
-------------

If you don't specify anything, web-framework will serve very boring text messages in case a page is not found.

You can either provide a single 404 page that is used for all 404 cases, or you can provide multiple different pages, by setting the right configuration.

To provide multiple 404 actions:

.. code-block:: php

    $site_config = array(
        'error_handlers' => array(
            '404' => array(
                'generic' => 'PageNotFound',
                'product' => 'ProductNotFound',
            ),
        ),
    );

In case any code calls `$this->exit_send_404();`, the generic mapping is used, and `App\Actions\PageNotFound` is called.

For specific 404 cases, code can call `$this->exit_send_404('product');` and then `App\Actions\ProductNotFound` is called.

