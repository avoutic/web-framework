# Middleware Management

This document provides a guide for developers on how to define and use middleware in the WebFramework. Middleware is used to process requests and responses in a Slim application, allowing you to add functionality such as authentication, logging, and error handling.

## Defining Middleware in Configuration

Middleware is defined in the configuration file under the `middlewares` key. The configuration is divided into two sections: `pre_routing` and `post_routing`.

- **`pre_routing`**: Middleware in this section is executed before the routing process receives the request and after the routing process has handled the request. It is therefore mainly used to catch errors that occur during routing or in the middlewares after routing.

- **`post_routing`**: Middleware in this section is executed after the routing process. It is used for tasks that need to be completed after determining which action to route to, such as starting a session or handling security.

### Middleware Order

Middleware is executed in the order it is defined in the configuration file. The first middleware in the list is the last to be executed (end of the stack), and the last middleware in the list is the first to be executed (start of the stack). This means that middleware is entered and exited in a Last-In-First-Out (LIFO) order.

### Example Configuration

Most applications will use the following configuration:

~~~
'pre_routing' => [
    // End of stack
    ErrorRedirectMiddleware::class,
    // Start of stack
],
'post_routing' => [
    // End of stack
    SecurityHeadersMiddleware::class,
    MessageMiddleware::class,
    JsonParserMiddleware::class,
    BlacklistMiddleware::class,
    CsrfValidationMiddleware::class,
    IpMiddleware::class,
    SessionStartMiddleware::class,
    // Start of stack
],
~~~

In this configuration, `ErrorRedirectMiddleware` is executed first in the `pre_routing` stack, and `SessionStartMiddleware` is executed first in the `post_routing` stack.

## Routing and Middleware

Routing is done between the `pre_routing` and `post_routing` middleware stacks by the `RoutingMiddleware` of the Slim framework. The first entry of `post_routing` will call the actual action routed to.

## Purpose of Standard Middleware Implementations

Here is a description of the purpose of each standard middleware implementation:

### ErrorRedirectMiddleware

- **Purpose**: Handles errors and exceptions by redirecting to appropriate error pages or handling exceptions gracefully.

### TransactionMiddleware

- **Purpose**: Ensures that the request is wrapped in a database transaction, committing the transaction if the request is successful or rolling it back if an error occurs.

### SecurityHeadersMiddleware

- **Purpose**: Adds security headers to the response, such as `X-Frame-Options` to prevent clickjacking and a random header to mitigate BREACH attacks.

### MessageMiddleware

- **Purpose**: Handles messages passed via URL parameters, adding them to the message service for display to the user.

### JsonParserMiddleware

- **Purpose**: Parses JSON request bodies and adds the parsed data to the request attributes for easy access.

### BlacklistMiddleware

- **Purpose**: Checks if a request is blacklisted based on IP address or user ID, throwing a `BlacklistException` if the request is blacklisted.

### CsrfValidationMiddleware

- **Purpose**: Validates CSRF tokens to protect against cross-site request forgery attacks, adding an error message if validation fails.

### IpMiddleware

- **Purpose**: Adds the client IP address to the request attributes for use in other middleware or actions.

### SessionStartMiddleware

- **Purpose**: Starts a session for the request, allowing session data to be accessed and modified during the request lifecycle.