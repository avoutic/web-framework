# Routing

This document provides a guide for developers on how to set up routes in the WebFramework. Routes define the endpoints of your application and map them to specific actions or controllers. WebFramework uses the Slim framework for request handling. So the basic routing rules are defined in the Slim framework documentation.

## Setting Up Routes

Routes are defined in the configuration under the `routes` key. The configuration specifies the route classes to load, which are responsible for registering the routes with the Slim application.

### Configuration

In your `config/config.php` file, specify the route classes to load:

For available configuration options and default settings, see `config/base_config.php`.

~~~
<?php

return [
    // Other configuration settings...

    'routes' => [
        \App\Routes\Unauthenticated::class,
        \App\Routes\Authenticated::class,
    ],
];
~~~

### Route Classes

Route classes should implement the `RouteSet` interface and are usually placed in the `routes` directory. But you could place them anywhere you want as long as you configure Composer and the autoloader can find them. Each route class is responsible for registering its routes with the Slim application.

## Example: Unauthenticated Routes

Here's an example of a route class for unauthenticated routes using the basic WebFramework actions for authentication. You don't need to use these actions, but they provide a good starting point.

~~~
<?php

namespace App\Routes;

use Slim\App;
use WebFramework\Http\RouteSet;

class Unauthenticated implements RouteSet
{
    public function register(App $app): void
    {
        // Login / verification related
        //
        $app->get('/login', \WebFramework\Actions\Login::class);
        $app->post('/login', \WebFramework\Actions\Login::class);
        $app->get('/login/verify', \WebFramework\Actions\LoginVerify::class);

        $app->get('/logoff', \WebFramework\Actions\Logoff::class);

        $app->get('/change-email', \WebFramework\Actions\ChangeEmail::class);
        $app->post('/change-email', \WebFramework\Actions\ChangeEmail::class);
        $app->get('/change-email/verify', \WebFramework\Actions\ChangeEmailVerify::class);

        $app->get('/reset-password', \WebFramework\Actions\ResetPassword::class);
        $app->post('/reset-password', \WebFramework\Actions\ResetPassword::class);
        $app->get('/reset-password/verify', \WebFramework\Actions\ResetPasswordVerify::class);

        $app->get('/verify', \WebFramework\Actions\Verify::class);
        $app->post('/verify', \WebFramework\Actions\Verify::class);

        $app->get('/verify', \WebFramework\Actions\Verify::class);
        $app->post('/verify', \WebFramework\Actions\Verify::class);
        $app->post('/verify/resend', \WebFramework\Actions\VerifyResend::class);

        // Registration related
        //
        $app->get('/register', \WebFramework\Actions\Register::class);
        $app->post('/register', \WebFramework\Actions\Register::class);
        $app->get('/register/verify', \WebFramework\Actions\RegisterVerify::class);
    }
}
~~~

## Example: Authenticated Routes

For authenticated routes, you can use the `WebFramework\Middleware\LoggedInMiddleware` to ensure that the user is logged in before accessing certain routes.

### Authenticated Route Group

You can then create a route group for authenticated routes, using the `LoggedInMiddleware` to protect the routes:

~~~
<?php

namespace App\Routes;

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use WebFramework\Http\RouteSet;
use WebFramework\Middleware\LoggedInMiddleware;

// Authenticated (but not linked to account type)
//
class Authenticated implements RouteSet
{
    public function __construct(
        private LoggedInMiddleware $loggedInMiddleware,
    ) {
    }

    public function register(App $app): void
    {
        $app->group('', function (RouteCollectorProxy $group) {
            $group->get('/', \App\Actions\Main::class);
            
            $group->get('/change-password', \WebFramework\Actions\ChangePassword::class);
            $group->post('/change-password', \WebFramework\Actions\ChangePassword::class);

            $group->get('/settings', \App\Actions\Settings::class);
        })
            ->add($this->loggedInMiddleware)
        ;
    }
}
~~~

In this example, the `Authenticated` route group uses the `LoggedInMiddleware` to ensure that only authenticated users can access the routes within the group.