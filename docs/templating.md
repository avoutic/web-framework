# Rendering and Templating

This document provides a guide for developers on how rendering and templating work in the WebFramework. It covers the default renderer, how to create your own action, set up parameters, and pass them to a template.

## Overview of Rendering in WebFramework

The WebFramework uses the `RenderService` interface to define the contract for rendering services. The default implementation of this interface is the `LatteRenderService`, which uses the Latte templating engine to render templates.

### Default Renderer: LatteRenderService

The `LatteRenderService` is the default renderer in WebFramework. It uses the Latte templating engine to render templates and return responses. Latte is a powerful and secure templating engine for PHP, known for its clean syntax and flexibility.

#### Key Features of Latte

- **Secure**: Automatically escapes output to prevent XSS attacks.
- **Flexible**: Supports custom filters and functions.
- **Fast**: Compiles templates to PHP code for high performance.

For more information about Latte, visit the [Latte Framework](https://latte.nette.org) website.

## Creating Your Own Action

To create your own action in WebFramework, you need to define a class that handles the request and response. This class will use the `RenderService` to render a template.

### Example Action: HelloWorldAction

~~~php
<?php

namespace App\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Presentation\RenderService;

class HelloWorldAction
{
    public function __construct(
        private RenderService $renderService,
    ) {}

    public function __invoke(Request $request, ResponseInterface $response): ResponseInterface
    {
        $params = ['name' => 'World'];

        return $this->renderService->render($request, $response, 'hello-world.latte', $params);
    }
}
~~~

In this example, the `HelloWorldAction` class uses the `RenderService` to render a template called `hello-world.latte`, passing a parameter `name` with the value `World`.

## Setting Up Parameters and Passing Them to a Template

Parameters are passed to the template as an associative array. These parameters can be accessed within the template using their keys.

### Example Template: hello-world.latte

~~~latte
Hello, {$name}!
~~~

In this template, the `name` parameter is accessed using the `{$name}` syntax, and the output will be "Hello, World!".

## Filters, Functions and Values for Every Template

The Latte `Engine` comes from the container. To add your own filters and functions, or values that every template can use, decorate it in your definitions. The decorator gets the engine the framework created, adds to it and returns it:

~~~php
<?php

use Latte\Engine;
use Psr\Container\ContainerInterface;
use WebFramework\Security\CsrfService;

return [
    Engine::class => DI\decorate(function (Engine $engine, ContainerInterface $c) {
        // A filter: {$price|money}
        $engine->addFilter('money', fn (int $cents): string => number_format($cents / 100, 2));

        // A function: {csrfToken()}
        $engine->addFunction('csrfToken', fn (): string => $c->get(CsrfService::class)->getToken());

        // A value: {$this->global->appName}
        $engine->addProvider('appName', 'My Application');

        return $engine;
    }),
];
~~~

Things to know:

- A function is called when the template uses it. So it can give something that belongs to the current request, such as a CSRF token or the rights of the user, by asking a service at that moment. A value that is added with `addProvider()` is fixed when the engine is created.
- Functions and the values under `$this->global` are separate from the parameters an action passes to a template, so they cannot be overwritten by them, or overwrite them.
- Do not create the `Engine` yourself in a definition to add a filter. A decorator keeps working when the framework changes how the engine is set up.

## Compiled Templates

Latte compiles a template to PHP code, and keeps the result in the directory of `latte.tmp_dir`, which is `/tmp/latte` by default. The code in that directory is run by your application. On a server that is shared with others, set it to a directory that only your application can write to:

~~~php
<?php

return [
    'latte' => [
        'tmp_dir' => '/var/cache/myapp/latte',
    ],
];
~~~

