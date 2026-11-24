# Configuration Management

This document provides a guide on how to construct the configuration tree for your application and how to access the configuration items from application code.

## Configuration Layering

The WebFramework uses a layered approach to configuration, allowing multiple configuration files to be merged to form the final configuration. This is managed by the `ConfigBuilder` class. The `TaskRunner` class uses the `ConfigBuilder` class to build the configuration. So in practice, you don't need to worry about the `ConfigBuilder` class.

### Environment Variables and .env Files

Before loading configuration files, the framework automatically loads environment variables from `.env` files in the following order:

1. `.env` - Main environment file (should be committed to version control with default values)
2. `.env.local` - Local overrides (should be gitignored and contain local/sensitive values)

Environment variables take precedence over .env files, and .env.local takes precedence over .env. This allows for flexible configuration management across different environments.

## Default Configuration Files

The `TaskRunner` class specifies a default set of configuration files that are used to build the configuration. These files are:

- `/vendor/avoutic/web-framework/config/base_config.php`
- `/config/config.php`
- `?/config/config_{APP_ENV}.php`, when the `APP_ENV` environment variable is set
- `?/config/config_local.php`

The `?` before a file path indicates that the file is optional. If it exists, it will be included; otherwise, it will be ignored.

Meaning that if will load the base configuration file from the WebFramework, then the application configuration file, then the configuration file for the environment if it exists, and finally the local configuration file if it exists.

### Configuration per Environment

With `APP_ENV=staging` in the environment, `/config/config_staging.php` is loaded if it exists, on top of `config.php` and below `config_local.php`. Use it for what differs per environment and belongs in version control. `APP_ENV` must be a real environment variable: it is read before the `.env` file is loaded, as it also selects which `.env` file is used. With `APP_ENV=local` no extra file is loaded, as `config_local.php` already is.

### How Files Are Merged

Each file is merged on top of the files before it:

- Arrays with named keys are merged key by key, at every level. A file only has to contain the keys it changes.
- Lists (arrays without keys, such as `definition_files`, `routes` or the middleware stacks) are replaced as a whole. The order of a list often matters, so a later file states the complete list.

### Extending a List

To add to a list instead of restating it, a configuration file can return a function instead of an array. The function gets the configuration of the files before it, and returns the configuration to merge on top:

~~~php
<?php

return fn (array $config) => [
    'definition_files' => [
        ...$config['definition_files'],
        '?local_definitions.php',
    ],
];
~~~

As it is plain PHP, you can also insert at a position, for example to add a middleware before another one:

~~~php
<?php

use App\Middleware\MaintenanceMiddleware;
use WebFramework\Middleware\CsrfValidationMiddleware;

return function (array $config): array {
    $stack = $config['middlewares']['post_routing'];

    array_splice($stack, array_search(CsrfValidationMiddleware::class, $stack, true), 0, [MaintenanceMiddleware::class]);

    return [
        'middlewares' => [
            'post_routing' => $stack,
        ],
    ];
};
~~~

What the function returns is merged like the array of any other configuration file.

### Available Configuration Options

For a complete list of all available WebFramework configuration options and their default settings, see `config/base_config.php`. This file contains all the default configuration values used by the framework, including:

- Application settings (debug, production, timezone, etc.)
- Logging configuration
- Security settings (authentication, blacklist, encryption keys, etc.)
- Action configurations (login, password reset, email change, etc.)
- Middleware configuration
- Routing configuration
- Translation settings
- Mailer configuration
- And more

When configuring your application, you can override any of these defaults in your `config.php` or `config_local.php` files.

### Setting Another Set of Files

To use a different set of configuration files, you can call the `setConfigFiles` method on the `TaskRunner` instance, passing an array of file paths.

As `base_config.php` is the base configuration file for the WebFramework, it should always be included.

#### Example

~~~php
<?php

// Rest of the setup code...

$taskRunner = new TaskRunner('/path/to/app');
$taskRunner->setConfigFiles([
    '/config/base_config.php',
    '/config/custom_env.php',
    '/config/custom_local.php',
]);
$taskRunner->build();
~~~

In this example, the configuration is built using the specified custom configuration files.

## ConfigBuilder

The `ConfigBuilder` class is actually responsible for building and managing the configuration. It allows you to merge multiple configuration files on top of each other. As mentioned earlier, you don't need to worry about the `ConfigBuilder` class. The `TaskRunner` class uses it internally to build the configuration.

### Key Methods

- **`mergeConfigOnTop(array $config): void`**: Merges a configuration array on top of the existing global configuration.
- **`loadConfigFile(string $configLocation): array`**: Loads a configuration file and returns its contents as an array.
- **`buildConfig(array $configs): array`**: Builds the configuration by merging multiple configuration files. The files are specified in order, and each file is merged on top of the previous ones.

### Example Usage

~~~php
<?php

// Rest of the setup code...

$configBuilder = new ConfigBuilder('/path/to/app');
$finalConfig = $configBuilder->buildConfig([
    '/config/default.php',
    '/config/environment.php',
    '/config/local.php',
]);
~~~

In this example, the configuration is built by merging `default.php`, `environment.php`, and `local.php` in that order. Each subsequent file can override values from the previous ones.

## Configuration File Format

Configuration files are PHP files that return an associative array. Each file can define any number of configuration settings, which are merged into the final configuration.

#### Example Configuration File

~~~php
<?php

return [
    'service' => [
        'host' => env('SERVICE_HOST', 'localhost'),
        'username' => env('SERVICE_USER', 'root'),
        'password' => env('SERVICE_PASSWORD', ''),
    ],
];
~~~

In this example, the configuration file defines settings for the database connection and application settings.

## Accessing Configuration Items

Once the configuration is built, it can be accessed using the `ConfigService` class. This class provides methods to retrieve configuration values using dot notation.

### ConfigService

The `ConfigService` class provides access to configuration values.

#### Key Methods

- **`get(string $location = ''): mixed`**: Retrieves a configuration value by its location using dot notation. If no location is provided, it returns the entire configuration array.

#### Example Usage with Dependency Injection

In a typical application, you would use dependency injection to access the `ConfigService`. Here's an example of how you might do this in a class:

~~~php
<?php

use WebFramework\Config\ConfigService;

class ExampleClass
{
    public function __construct(
        private ConfigService $configService,
    ) {}

    public function getServiceHost(): string
    {
        return $this->configService->get('service.host');
    }
}
~~~

In this example, the `ExampleClass` receives the `ConfigService` as a dependency through its constructor. The `getServiceHost()` method then uses the `ConfigService` to retrieve the `host` value from the `service` configuration.

## Environment Variable Support

The framework supports environment variables for common configuration values. You can use `.env` files to set these values:

### Service Configuration

Providing environment variables for the service configuration just defined in the above configuration file.

~~~
SERVICE_HOST=localhost
SERVICE_USER=myuser
SERVICE_PASSWORD=mypassword
~~~

### Application Settings

~~~
DEBUG=true
PRODUCTION=false
TIMEZONE=Europe/Amsterdam
~~~

### Security Settings

~~~
HMAC_KEY=your_secure_hmac_key
CRYPT_KEY=your_secure_crypt_key
RECAPTCHA_SITE_KEY=your_recaptcha_site_key
RECAPTCHA_SECRET_KEY=your_recaptcha_secret_key
~~~

### Auth Configuration Files

Environment variables can also be used in auth configuration files located in `/config/auth/`. For example, in `db_config.main.php`:

~~~php
<?php

return [
    'database_host' => env('DB_HOST', 'localhost'),
    'database_user' => env('DB_USER', ''),
    'database_password' => env('DB_PASSWORD', ''),
    'database_database' => env('DB_NAME', ''),
];
~~~

### Precedence Order

Configuration values are resolved in the following order (highest to lowest precedence):

1. System environment variables
2. Relevant `.env` file depending on `APP_ENV` (e.g. `.env.local` or `.env.production`, and '.env' if no `APP_ENV` is set or no `.env.APP_ENV` file exists)
3. Configuration files (by default `config_local.php` > `config.php` > `base_config.php`)

This allows you to override any configuration value at any level while maintaining sensible defaults.
