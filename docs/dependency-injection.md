# Dependency Injection

This document provides a guide for developers on how to use and configure dependency injection in the WebFramework. The framework uses PHP-DI for managing dependencies, allowing you to define and override services and classes in a flexible manner.

## Overview

Dependency injection in WebFramework is managed using PHP-DI, a powerful dependency injection container for PHP. It allows you to define how classes and services are instantiated and configured, making it easy to manage dependencies across your application.

The `BootstrapService` and `SlimAppTask` classes are responsible for setting up the dependency injection container and registering services. They use the configured definition files to do this.

## Default Definition Files

The base configuration specifies a default set of definition files that are used for dependency injection. These files are:

- `/vendor/avoutic/web-framework/definitions/definitions.php`
- `/definitions/app_definitions.php`

Meaning that if will load the base definition file from the WebFramework, then the application definition file.

### Setting Another Set of Files

To set your own definition files, you need to specify them in the `base_config.php` file under the `definition_files` key. These files contain PHP-DI definitions that configure how services and classes are instantiated.

For available configuration options and default settings, see `config/base_config.php`.

If the file starts with a `?`, it will be ignored if it does not exist. This is useful for local configuration files that you don't want to commit to version control.

### Example Configuration

~~~php
<?php

return [
    // Other configuration settings...

    'definition_files' => [
        '/vendor/avoutic/web-framework/definitions/definitions.php',
        '/definitions/my_definitions.php', // Your custom definitions
        '?/definitions/local_definitions.php', // Local definitions that are not committed
    ],
];
~~~

In this example, `my_definitions.php` is a custom definition file that you can use to define your own services and override existing ones.

## Overriding Classes

To override a class like `Instrumentation`, you need to provide a new implementation in your definition file. This is done using PHP-DI's `autowire` and `constructor` methods.

### Example: Overriding Instrumentation

Suppose you want to replace the default `Instrumentation` implementation with a custom one. You can do this by adding the following entry to your definition file:

~~~php
<?php

use App\Instrumentation\CustomInstrumentation;
use WebFramework\Diagnostics\Instrumentation;

return [
    Instrumentation::class => DI\autowire(CustomInstrumentation::class),
];
~~~

In this example, `CustomInstrumentation` is a class that implements the `Instrumentation` interface. By using `DI\autowire`, PHP-DI will automatically inject dependencies into the `CustomInstrumentation` class.

For more information on PHP-DI, see the [PHP-DI documentation](https://php-di.org/doc/index.html).