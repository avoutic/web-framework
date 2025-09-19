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
- `?/config/config_local.php`

The `?` before a file path indicates that the file is optional. If it exists, it will be included; otherwise, it will be ignored.

Meaning that if will load the base configuration file from the WebFramework, then the application configuration file, and finally the local configuration file if it exists.

### Setting Another Set of Files

To use a different set of configuration files, you can call the `setConfigFiles` method on the `TaskRunner` instance, passing an array of file paths.

As `base_config.php` is the base configuration file for the WebFramework, it should always be included.

#### Example

~~~php
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
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'username' => 'root',
        'password' => '',
        'dbname' => 'webframework',
    ],
    'app' => [
        'debug' => true,
        'timezone' => 'UTC',
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
use WebFramework\Core\ConfigService;

class ExampleClass
{
    public function __construct(
        private ConfigService $configService,
    ) {}

    public function getDatabaseHost(): string
    {
        return $this->configService->get('database.host');
    }
}
~~~

In this example, the `ExampleClass` receives the `ConfigService` as a dependency through its constructor. The `getDatabaseHost()` method then uses the `ConfigService` to retrieve the `host` value from the `database` configuration.

## Environment Variable Support

The framework supports environment variables for common configuration values. You can use `.env` files to set these values:

### Database Configuration

~~~
DB_HOST=localhost
DB_PORT=3306
DB_USER=myuser
DB_PASSWORD=mypassword
DB_NAME=mydatabase
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

This maintains backwards compatibility while providing the enhanced type-safe environment variable access.

### Precedence Order

Configuration values are resolved in the following order (highest to lowest precedence):

1. System environment variables
2. Relevant `.env` file depending on `APP_ENV` (e.g. `.env.local` or `.env.production`, and '.env' if no `APP_ENV` is set or no `.env.APP_ENV` file exists)
3. Configuration files (by default `config_local.php` > `config.php` > `base_config.php`)

This allows you to override any configuration value at any level while maintaining sensible defaults.
