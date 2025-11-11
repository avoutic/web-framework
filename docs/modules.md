# WebFramework Modules

WebFramework provides a modular architecture that allows you to extend its functionality through installable modules. These modules integrate seamlessly with the core framework and provide additional features for specific use cases.

## Available Modules

The following modules are available for WebFramework:

### WebFramework MySQL

**Package**: `avoutic/web-framework-mysql`

Provides MySQL database integration for WebFramework.

**Features**:
- MySQL database connectivity
- Integration with WebFramework's database layer

**Installation**:
```bash
composer require avoutic/web-framework-mysql
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-mysql/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Add the following `db_config.main.php` to your auth config directory (`config/auth`):

```php
<?php

return [
    'database_host' => env('DATABASE_HOST', 'localhost'),
    'database_user' => env('DATABASE_USER', 'your_user'),
    'database_password' => env('DATABASE_PASSWORD', 'your_password'),
    'database_database' => env('DATABASE_DATABASE', 'your_database')
];
```

**Repository**: [github.com/avoutic/web-framework-mysql](https://github.com/avoutic/web-framework-mysql)

---

### WebFramework Redis

**Package**: `avoutic/web-framework-redis`

Provides Redis caching integration for WebFramework.

**Features**:
- Redis-based caching implementation
- Support for cache tags
- Automatic instrumentation for performance tracking
- PSR-16 compliant cache interface

**Installation**:
```bash
composer require avoutic/web-framework-redis
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-redis/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Override the `Cache` interface in your application definition file (`definitions/app_definitions.php`):

```php
<?php

use DI;
use WebFramework\Cache\Cache;

return [
    Cache::class => DI\autowire(\WebFramework\Redis\RedisCache::class),
];
```

3. Add the following `redis.php` to your auth config directory (`config/auth`):

```php
<?php

return [
    'hostname' => env('REDIS_HOST', 'localhost'),
    'port' => env('REDIS_PORT', 6379),
    'password' => env('REDIS_PASSWORD', 'your_password')
];
```

**Repository**: [github.com/avoutic/web-framework-redis](https://github.com/avoutic/web-framework-redis)

---

### WebFramework Postmark

**Package**: `avoutic/web-framework-postmark`

Provides Postmark email service integration for WebFramework.

**Features**:
- Send raw emails via Postmark
- Send template-based emails via Postmark
- Automatic instrumentation for email sending
- Configurable default sender email
- Error handling for common Postmark API issues

**Installation**:
```bash
composer require avoutic/web-framework-postmark
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-postmark/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Override the `MailService` interface (and optionally `MailBackend` for queued emails) in your application definition file (`definitions/app_definitions.php`):

For synchronous email sending:

```php
<?php

use DI;
use WebFramework\Mail\MailService;
use WebFramework\Postmark\PostmarkMailService;

return [
    MailService::class => DI\autowire(PostmarkMailService::class),
];
```

For asynchronous (queued) email sending:

```php
<?php

use DI;
use WebFramework\Mail\MailService;
use WebFramework\Mail\MailBackend;
use WebFramework\Mail\QueuedMailService;
use WebFramework\Postmark\PostmarkMailService;

return [
    MailService::class => DI\autowire(QueuedMailService::class),
    MailBackend::class => DI\autowire(PostmarkMailService::class),
];
```

3. Add the following `postmark.php` to your auth config directory (`config/auth`):

```php
<?php

return 'your-api-key-here';
```

**Repository**: [github.com/avoutic/web-framework-postmark](https://github.com/avoutic/web-framework-postmark)

---

### WebFramework Browserless

**Package**: `avoutic/web-framework-browserless`

Provides PDF generation capabilities using the Browserless service.

**Features**:
- Generate PDFs from web pages
- Stream PDF output
- Integration with WebFramework's response system

**Installation**:
```bash
composer require avoutic/web-framework-browserless
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-browserless/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Add the following `browserless.php` to your auth config directory (`config/auth`):

```php
<?php

return [
    'local_server' => 'http://your-local-server',
    'pdf_endpoint' => 'https://your-browserless-instance/pdf',
    'token' => 'your-browserless-token'
];
```

**Usage**:

The module provides methods to generate PDFs from web pages:

```php
$browserless->outputPdf('/path/to/page', 'output.pdf');
// or
$stream = $browserless->outputStream('/path/to/page');
```

**Repository**: [github.com/avoutic/web-framework-browserless](https://github.com/avoutic/web-framework-browserless)

---

### WebFramework Sentry

**Package**: `avoutic/web-framework-sentry`

Provides Sentry instrumentation for WebFramework, enabling performance monitoring and distributed tracing.

**Features**:
- Performance monitoring
- Distributed tracing
- Error tracking integration
- Implements WebFramework Instrumentation interface

**Installation**:
```bash
composer require avoutic/web-framework-sentry
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-sentry/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Override the `Instrumentation` interface in your application definition file (`definitions/app_definitions.php`):

```php
<?php

use DI;
use WebFramework\Diagnostics\Instrumentation;

return [
    Instrumentation::class => DI\autowire(\WebFramework\Sentry\SentryInstrumentation::class),
];
```

**Repository**: [github.com/avoutic/web-framework-sentry](https://github.com/avoutic/web-framework-sentry)

---

### WebFramework Stripe

**Package**: `avoutic/web-framework-stripe`

Provides Stripe integration for WebFramework, offering a clean interface to interact with Stripe's API for handling payments, subscriptions, and webhooks.

**Features**:
- Stripe customer management
- Product and price creation
- Subscription handling
- Webhook verification
- Invoice retrieval
- Production and development environment support

**Installation**:
```bash
composer require avoutic/web-framework-stripe
```

**Configuration**:

1. Add the module's definition file to your `config.php`:

```php
<?php

return [
    'definition_files' => [
        '../vendor/avoutic/web-framework/definitions/definitions.php',
        '../vendor/avoutic/web-framework-stripe/definitions/definitions.php',
        'app_definitions.php',
    ],
];
```

2. Add the following `stripe.php` to your auth config directory (`config/auth`):

```php
<?php

return [
    'api_key' => 'your_stripe_secret_key',
    'publishable_api_key' => 'your_publishable_api_key',
    'endpoint_secret' => 'your_webhook_endpoint_secret'
];
```

**Repository**: [github.com/avoutic/web-framework-stripe](https://github.com/avoutic/web-framework-stripe)

---

## Installing Modules

All modules are installed via Composer. After installation, you typically need to:

1. **Install the package**: Use `composer require` to add the module to your project
2. **Register definitions**: Add the module's definition file to your `definition_files` array in your `config.php` file
3. **Override interfaces** (if required): Some modules require you to override default interfaces in your application definition file (`definitions/app_definitions.php`). See each module's configuration section for details.
4. **Configure the module**: Add the appropriate configuration file to your `config/auth` directory

**Note**: Modules that override core interfaces (like `Cache`, `MailService`, or `Instrumentation`) require additional PHP-DI configuration in your application definition file. The Sentry module is an exception as it automatically overrides the `Instrumentation` interface in its own definition file.

## Module Compatibility

All modules listed above are compatible with WebFramework 8.x and require PHP 8.2 or higher. Always check the module's README file for the latest compatibility information and specific requirements.

## Finding More Modules

For the latest list of available modules and their documentation, visit the [avoutic GitHub page](https://github.com/avoutic) or search for `avoutic/web-framework-*` packages on [Packagist](https://packagist.org).

