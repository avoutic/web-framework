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

        $app->get('/logoff', \WebFramework\Actions\Logoff::class);

        $app->get('/send-verify', \WebFramework\Actions\SendVerify::class);
        $app->get('/verify', \WebFramework\Actions\Verify::class);
        $app->get('/change-email-verify', \WebFramework\Actions\ChangeEmailVerify::class);

        $app->get('/forgot-password', \WebFramework\Actions\ForgotPassword::class);
        $app->post('/forgot-password', \WebFramework\Actions\ForgotPassword::class);

        $app->get('/reset-password', \WebFramework\Actions\ResetPassword::class);

        // Registration related
        //
        $app->get('/register', \WebFramework\Actions\Register::class);
    }
}
~~~

## Example: Authenticated Routes

For authenticated routes, you can create a middleware to ensure that the user is logged in before accessing certain routes.

### LoggedInMiddleware

Here's an example of a `LoggedInMiddleware` class that checks if a user is authenticated:

~~~
<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Security\AuthenticationService;

class LoggedInMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthenticationService $authenticationService,
    ) {}

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        if (!$this->authenticationService->isAuthenticated())
        {
            throw new HttpUnauthorizedException($request);
        }

        return $handler->handle($request);
    }
}
~~~

### Authenticated Route Group

You can then create a route group for authenticated routes, using the `LoggedInMiddleware` to protect the routes:

~~~
<?php

namespace App\Routes;

use App\Middleware\LoggedInMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use WebFramework\Http\RouteSet;

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
            $group->get('/settings', \App\Actions\Settings::class);
        })
            ->add($this->loggedInMiddleware)
        ;
    }
}
~~~

In this example, the `Authenticated` route group uses the `LoggedInMiddleware` to ensure that only authenticated users can access the routes within the group.