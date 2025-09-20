# Emitting Responses

This document provides a guide for developers on how to generate responses in an Action using the `ResponseEmitter` or via exceptions through the `ErrorRedirectMiddleware`. It also covers how to build redirects programmatically.

## Generating Responses in an Action

In WebFramework, responses can be generated directly using the `ResponseEmitter` or indirectly by throwing exceptions that are handled by the `ErrorRedirectMiddleware`.

### Using ResponseEmitter

The `ResponseEmitter` class provides methods to generate various types of responses, such as redirects, errors, and standard HTTP responses.

#### Example: Generating a Redirect Response

~~~php
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

## Building Redirects Programmatically

The `ResponseEmitter` service provides methods to build and generate redirect responses programmatically. This allows you to construct redirects with dynamic parameters and query strings.

### Example: Building a Simple Redirect

~~~php
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