# Emitting Responses

This document provides a guide for developers on how to generate responses in an Action using the `ResponseEmitter` or via exceptions through the `ErrorRedirectMiddleware`. It also covers how to build redirects programmatically.

## Generating Responses in an Action

In WebFramework, responses can be generated directly using the `ResponseEmitter` or indirectly by throwing exceptions that are handled by the `ErrorRedirectMiddleware`.

### Using ResponseEmitter

The `ResponseEmitter` class provides methods to generate various types of responses, such as redirects, errors, and standard HTTP responses.

#### Example: Generating a Redirect Response

~~~php
<?php

use WebFramework\Http\ResponseEmitter;

class ExampleAction
{
    public function __construct(
        private ResponseEmitter $responseEmitter,
    ) {}

    public function __invoke(): ResponseInterface
    {
        return $this->responseEmitter->redirect('/home');
    }
}
~~~

### Using Exceptions

Exceptions can be thrown in an Action or Service class to trigger specific responses. The `ErrorRedirectMiddleware` (assuming it is enabled) handles these exceptions and generates the appropriate HTTP response.

#### Exceptions Handled by ErrorRedirectMiddleware

- **RedirectException**: Triggers a redirect to a specified URL.
  - **HTTP Code**: 302 (Found)

- **HttpForbiddenException**: Indicates that access to the requested resource is forbidden.
  - **HTTP Code**: 403 (Forbidden)

- **HttpNotFoundException**: Indicates that the requested resource was not found.
  - **HTTP Code**: 404 (Not Found)

- **HttpUnauthorizedException**: Indicates that authentication is required to access the resource.
  - **HTTP Code**: 401 (Unauthorized)

- **BlacklistException**: Indicates that the user is blacklisted.
  - **HTTP Code**: 403 (Forbidden)

- **Throwable**: Any other unhandled exception results in a generic error response.
  - **HTTP Code**: 500 (Internal Server Error)

## Error Handlers

The `ResponseEmitter` uses configurable error handlers to generate custom error pages for various HTTP error codes. Error handlers allow you to provide user-friendly error pages instead of the default simple text responses.

### Configuration

Error handlers are configured in your configuration file under the `error_handlers` key. Each error handler should be set to a fully qualified class name of an action class that can be resolved by the dependency injection container.

~~~php
<?php

return [
    'error_handlers' => [
        '403' => \App\Actions\Error403::class,
        '404' => \App\Actions\Error404::class,
        '405' => \App\Actions\Error405::class,
        '500' => \App\Actions\Error500::class,
        'blacklisted' => \App\Actions\Blacklisted::class,
    ],
];
~~~

### Available Error Handlers

The following error handlers can be configured:

- **`403`**: Handles forbidden access errors (HTTP 403 Forbidden)
- **`404`**: Handles not found errors (HTTP 404 Not Found)
- **`405`**: Handles method not allowed errors (HTTP 405 Method Not Allowed)
- **`500`**: Handles internal server errors (HTTP 500 Internal Server Error)
- **`blacklisted`**: Handles blacklisted user access (HTTP 403 Forbidden)

### Error Handler Implementation

Error handler classes must be callable (implement `__invoke`) and accept two parameters:
1. `Request` - The PSR-7 server request object
2. `Response` - The PSR-7 response object (pre-configured with the appropriate status code)

The error handler should return a `ResponseInterface` with the rendered error page.

#### Example: Creating a 404 Error Handler

~~~php
<?php

namespace App\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use WebFramework\Presentation\RenderService;

class Error404
{
    public function __construct(
        private RenderService $renderer,
    ) {}

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        return $this->renderer->render($request, $response, 'errors/404.latte', [
            'title' => 'Page Not Found',
            'message' => 'The page you are looking for could not be found.',
        ]);
    }
}
~~~

#### Example: Creating a 500 Error Handler

~~~php
<?php

namespace App\Actions;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Http\Response;
use WebFramework\Presentation\RenderService;

class Error500
{
    public function __construct(
        private RenderService $renderer,
    ) {}

    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        // Access error report if available
        $errorReport = $request->getAttribute('error_report');

        return $this->renderer->render($request, $response, 'errors/500.latte', [
            'title' => 'Internal Server Error',
            'message' => 'An error occurred while processing your request.',
            'errorReport' => $errorReport,
        ]);
    }
}
~~~

### Error Handler Behavior

Error handlers are only invoked for non-JSON requests. When a request has `Content-Type: application/json`, the `ResponseEmitter` automatically returns a JSON response instead of calling the error handler.

If an error handler is not configured (set to `null`) or cannot be resolved from the container, the `ResponseEmitter` falls back to a simple text response:

- **403**: "Forbidden"
- **404**: "Not Found"
- **405**: "Method Not Allowed"
- **500**: "Error: {details}" (includes error title and details)
- **blacklisted**: "Blacklisted"

### Error Report Attribute

For 500 errors, the request may include an `error_report` attribute (set by `ErrorRedirectMiddleware`) that contains detailed error information. This can be accessed in your error handler:

~~~php
$errorReport = $request->getAttribute('error_report');
if ($errorReport) {
    // Use error report for debugging or logging
}
~~~

## Building Redirects Programmatically

The `ResponseEmitter` service provides methods to build and generate redirect responses programmatically. This allows you to construct redirects with dynamic parameters and query strings.

### Example: Building a Simple Redirect

~~~php
<?php

use WebFramework\Http\ResponseEmitter;

class ExampleService
{
    public function __construct(
        private ResponseEmitter $responseEmitter,
    ) {}

    public function redirectToHome(): ResponseInterface
    {
        return $this->responseEmitter->redirect('/home');
    }
}
~~~

### Example: Building a Programmatic Redirect

~~~php
<?php

use WebFramework\Http\ResponseEmitter;

class ExampleService
{
    public function __construct(
        private ResponseEmitter $responseEmitter,
    ) {}

    public function redirectToUserProfile(int $userId): ResponseInterface
    {
        return $this->responseEmitter->redirect('/user/{id}', ['id' => $userId]);
    }
}
~~~

### Example: Building a Redirect with Query Parameters

~~~php
<?php

use WebFramework\Http\ResponseEmitter;

class ExampleService
{
    public function __construct(
        private ResponseEmitter $responseEmitter,
    ) {}

    public function redirectToSearch(string $query): ResponseInterface
    {
        return $this->responseEmitter->buildQueryRedirect('/search', [], ['q' => $query]);
    }
}
~~~

In these examples, the `ResponseEmitter` is used to construct redirect responses with dynamic path parameters and query strings.