# Logging

WebFramework offers a flexible logging system that revolves around PSR-3 loggers, channels, and the `ChannelManager`. This document explains how to work with loggers, how default channels are wired, and how you can override or introduce additional channels.

[Monolog](https://github.com/Seldaek/monolog) is used as the underlying logging library and included by default.

## ChannelManager and LogService

The `WebFramework\\Logging\\ChannelManager` resolves loggers by channel name. It uses the dependency injection container to look up services and caches resolved loggers for reuse. When no matching logger can be found the manager falls back to a `NullLogger`, so logging calls never break your application.

`WebFramework\\Logging\\LogService` is a convenience wrapper that mirrors the PSR-3 logging methods. You pass the channel name as the first argument and the message/level follows the usual PSR-3 signature:

~~~php
<?php

use WebFramework\Logging\LogService;

class InvoiceService
{
    public function __construct(private LogService $logService) {}

    public function createInvoice(): void
    {
        $this->logService->info('billing', 'Invoice created', ['customer' => 123]);
    }
}
~~~

It provides all the usual PSR-3 logging methods in this way with the channel name as the first argument: `log`, `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, and `debug`.

## Example Usage

To use the default channel in your class, you can just inject `LoggerInterface` and use its methods, as LoggerInterface is mapped to retrieve the default channel in the container:

~~~php
<?php

use Psr\Log\LoggerInterface;

class ExampleService
{
    public function __construct(private LoggerInterface $logger) {}

    public function logInfo(): void
    {
        $this->logger->info('Invoice created', ['customer' => 123]);
    }
}
~~~

To be able to send messages to other channels, you can inject `LogService` and use its methods:

~~~php
<?php

use WebFramework\Logging\LogService;

class ExampleService
{
    public function __construct(private LogService $logService) {}

    public function logInfo(): void
    {
        $this->logService->info('billing', 'Invoice created', ['customer' => 123]);
    }
}
~~~

## Default Channels

Out of the box, WebFramework provides two channels, for which it provides default definition placeholders in `definitions/definitions.php`:

- `channels.default`
- `channels.exception`

- `channels.default` writes messages of level `error` and higher to the PHP error log. This makes errors, including unhandled exceptions, visible without any configuration.
- `channels.exception` follows `channels.default`.

To define the Logger for these (and other) channels, you can override the definition in your definitions file (e.g. `definitions/app_definitions.php`). Because the exception channel follows the default channel, overriding `channels.default` is enough to send all framework logging to your own logger. Override `channels.exception` as well if you want unhandled exceptions somewhere else.

The framework logs an unhandled exception to both channels. As long as the exception channel follows the default channel, it shows up twice in the same log: once as a short line, and once with the error report as context.

### Bind the channels, not `LoggerInterface`

`LoggerInterface` resolves to the default channel. Do not bind `LoggerInterface` to your own logger: classes that inject `LoggerInterface` would use it, but the framework also logs through `LogService` by channel name, and those messages, including unhandled exceptions, would not reach your logger. Bind `channels.default` instead.

## Adding or Replacing Channel Definitions

Channel definitions live in the dependency injection container. By convention they use the `channels.*` naming scheme. Each definition must return a `Psr\Log\LoggerInterface`. For example:

```php
<?php

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

return [
    'channels.default' => function (): Logger {
        $logger = new Logger('default');
        $logger->pushHandler(new StreamHandler('/var/log/app.log', Level::Info));

        return $logger;
    },
    'channels.billing' => function (): Logger {
        $logger = new Logger('billing');
        $logger->pushHandler(new StreamHandler('/var/log/billing.log', Level::Debug));

        return $logger;
    },
];
```

Once such definitions exist, the `ChannelManager` can resolve the channel by asking the container for the corresponding service.

## Overriding Channels Via Configuration

The effective channels are determined by the `logging.channels` section in your configuration (see `config/base_config.php`). Each entry maps a channel name to a container id or to an inline configuration array:

```php
<?php

return [
    'logging' => [
        'channels' => [
            'default' => 'channels.default',
            'exception' => [
                'type' => 'file',
                'path' => '/tmp/app-exceptions.log',
                'level' => \Monolog\Level::Error,
            ],
        ],
    ],
];
```

- If the value is a string, the manager asks the container for that service id.
- If the value is an array with `type => 'file'`, the manager creates a `Monolog\Logger` on the fly that writes to `path` with an optional `level` (defaults to `Level::Debug`).

This mechanism lets you temporarily override a definition—handy for local debugging or when you want a different logger in production—without touching the DI definitions themselves. Changing the mapping to reference another definition immediately redirects the channel to that logger.

## Fallback Behaviour

A channel that is not listed in `logging.channels` uses the container entry with its name, `channels.<name>`. So for a new channel, a `channels.billing` definition is all you need. A line in `logging.channels` is only needed to point a channel at another entry.

When neither the configuration nor the container can supply a logger for a channel, the manager returns a `NullLogger`, so logging never fails. As that usually is a mistake, such as a typo in the name of the channel, a warning is logged on the default channel, once per channel.

With these tools you can keep the framework supplied `LoggerInterface` for generic logging, add specialised channels for specific domains, and override them per environment whenever needed.
