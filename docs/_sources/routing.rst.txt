Routing
=======

If nothing is configured, web-framework will only allow traffic to the default page (*main* by default). To make sure other pages get handled we have to populate the routing array.

Routes are configured in *includes/site\_logic.inc.php*. This file is the central switchroom of your application. Without it, web-framework is not called.

The function `register_routes()` contains all route statements for your application.

Adding a redirect
-----------------

A redirect is an instruction to return a 301 (or other redirect code) to the client and move the communication somewhere else.

In it's most basic form, a redirect is nothing more than a statement to redirect a method and URL to another URL, like this:

.. code-block:: php

    function register_routes()
    {
        register_redirect('GET /old_page', '/new-page');
    }

For a more dynamic redirect, we can also include regex-mapping to our redirect. In the third parameter to `register_redirect()` we tell it what kind of return code should be used. In the fourth parameter we provide a mapping from a name to the index of the regex matches. In this case we map the first match by the regex to the name `slug`.

.. code-block:: php

    function register_routes()
    {
        register_redirect('GET /old_category/(\w+)', '/new-category/{slug}',
                          301, array('slug' => 1));
    }

Adding a route
--------------

A route is a mapping that will result in a specific view Page being called and executed.

Let's start with a simple mapping to tell web-framework to send all requests for */dashboard* to your View in *views/dashboard.inc.php* with class name `PageDashboard` and to call `html_main()` on that object. That would look something like:

.. code-block:: php

    function register_routes()
    {
        register_route('GET /dashboard', 'dashboard', 'PageDashboard.html_main');
    }

In some cases the URL already contains relevant information for your View to use as input. In that case we can map regex-matches to input variables (that are filtered with your View's static `get_filter()` function.

.. code-block:: php

    function register_routes()
    {
        register_route('GET /product/(\w+)', 'product', 'PageProduct.html_main', array('slug'));
    }

This will map part of the URL to the `slug` input variable for your PageProduct View.

Handling 404s
-------------

If you don't specify anything, web-framework will serve very boring text messages in case a page is not found.

You can either provide a single 404 page that is used for all 404 cases, or you can provide multiple different pages, by setting the right configuration.

To provide multiple 404 page:

.. code-block:: php

    $site_config = array(
        'error_handlers' => array(
            '404' => array(
                'generic' => 'page_not_found',
                'product' => 'product_not_found',
            ),
        ),
    );

In case any code calls `$this->exit_send_404();`, the generic mapping is used, and *views/page_not_found.inc.php* is opened with the `PagePageNotFound` class called.

For specific 404 cases, code can call `$this->exit_send_404('product');` and then *views/product_not_found.inc.php* is opened with the `PageProductNotFound` class called.


