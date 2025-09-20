# Instrumentation

This document provides a guide for developers on the instrumentation system in WebFramework. Instrumentation is used to monitor and measure the performance of your application, allowing you to identify bottlenecks and optimize performance.

## Overview

Instrumentation in WebFramework is designed to track the execution time of various parts of your application. It is implemented using the `Instrumentation` interface, which provides methods for starting and finishing spans and transactions.

## When to Use Instrumentation

Instrumentation is useful in scenarios where you need to monitor the performance of your application, such as:

- Identifying slow database queries or external API calls.
- Measuring the execution time of specific actions or middleware.
- Tracking the overall performance of your application in production.

## Adding Instrumentation to Your Classes

To add instrumentation to your own classes, you need to inject the `Instrumentation` service and use its methods to start and finish spans.

### Example Usage

~~~php
use WebFramework\Diagnostics\Instrumentation;

class ExampleService
{
    public function __construct(
        private Instrumentation $instrumentation,
    ) {}

    public function performTask(): void
    {
        $span = $this->instrumentation->startSpan('example.perform_task');

        // Perform the task...

        $this->instrumentation->finishSpan($span);
    }
}
~~~

In this example, the `ExampleService` uses the `Instrumentation` service to measure the execution time of the `performTask` method.

## Existing Instrumentation Locations

Instrumentation is already used in various parts of the WebFramework to measure the speed of actions and middleware. Here are some key locations:

- **SlimAppTask**: Measures the time taken to handle a request in the Slim application.
- **InstrumentationMiddleware**: Measures the time taken to process each request.
- **SanityCheckRunner**: Measures the time taken to execute sanity checks.
- **CaptchaService**: Measures the time taken to validate captchas.
- **LatteRenderService**: Measures the time taken to render templates using Latte.
- **PostmarkMailService**: Measures the time taken to send emails using Postmark.
- **RedisCache**: Measures the time taken for cache operations using Redis.
- **MysqliDatabase**: Measures the time taken for database queries using MySQLi.

## Writing a Custom Instrumentation Handler

To write a custom instrumentation handler, you need to implement the `Instrumentation` interface. This interface defines the methods required for starting and finishing spans and transactions.

### Example Custom Instrumentation Handler

~~~php
namespace App\Instrumentation;

use WebFramework\Diagnostics\Instrumentation;

class CustomInstrumentation implements Instrumentation
{
    public function startTransaction(string $name, string $operation): mixed
    {
        // Start a new transaction with the given name and operation
        // Return a transaction object or identifier
    }

    public function finishTransaction(mixed $transaction): void
    {
        // Finish the given transaction
    }

    public function startSpan(string $name): mixed
    {
        // Start a new span with the given name
        // Return a span object or identifier
    }

    public function finishSpan(mixed $span): void
    {
        // Finish the given span
    }

    public function getCurrentTransaction(): mixed
    {
        // Return the current transaction object or identifier
    }

    public function setTransactionName(mixed $transaction, string $name): void
    {
        // Set the name of the given transaction
    }
}
~~~

### Integrating the Custom Handler

Once you have implemented your custom instrumentation handler, you need to register it in your [dependency injection container](dependency-injection.md). 
