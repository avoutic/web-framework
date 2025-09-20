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