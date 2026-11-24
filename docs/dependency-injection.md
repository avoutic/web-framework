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

Each entry is the path of a definition file:

- A path that starts with `/` is relative to your application directory, the same way configuration files are listed. For example `/vendor/avoutic/web-framework/definitions/definitions.php` or `/definitions/app_definitions.php`.
- Any other path is relative to the `definitions` directory of your application. For example `app_definitions.php` or `../vendor/avoutic/web-framework/definitions/definitions.php`.

If the entry starts with a `?`, the file is ignored if it does not exist. This is useful for local definition files that you don't want to commit to version control.

The files are loaded in the order they are listed, and a later file replaces the entries of an earlier one. So list the definitions of the framework first, then those of the modules you use, such as `/vendor/avoutic/web-framework-mysql/definitions/definitions.php`, and your own definitions last.

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

### Overriding a Class That Has Configured Parameters

Some framework services get values from the configuration through `constructorParameter()` in `definitions/definitions.php`. `LoginService` is an example:

~~~php
<?php

return [
    Security\LoginService::class => DI\autowire()
        ->constructorParameter('validityPeriodDays', DI\get('security.email_verification.validity_period_days')),
];
~~~

A definition in your own file replaces the definition of the framework completely. It is not merged with it. So when you bind such a service to your own subclass, you have to repeat its `constructorParameter()` lines:

~~~php
<?php

use App\Security\MyLoginService;
use WebFramework\Security\LoginService;

return [
    LoginService::class => DI\autowire(MyLoginService::class)
        ->constructorParameter('validityPeriodDays', DI\get('security.email_verification.validity_period_days')),
];
~~~

Without them the container cannot resolve the service, and reports that a parameter has no value defined. The same applies when you only want to change one parameter of a framework service: repeat the others as well.

Copy the lines from the entry of the service in `vendor/avoutic/web-framework/definitions/definitions.php`, and check that entry again when you upgrade the framework, as a new version can add a parameter.

Before you override a framework service, check if it has an extension point for what you need, such as the extension interfaces in `WebFramework\Security\Extension` or the `AccountStatusChecker`. Those are replaced with a single line and do not need any parameters.

## Checking That Everything Can Be Instantiated

A class whose constructor needs something the container cannot provide only fails when it is first used, which can be a request in production. `WebFramework\Testing\InstantiationSweep` finds those classes in a test: it tries to get every class of the namespaces you give it from the container.

~~~php
<?php

use Psr\Container\ContainerInterface;
use WebFramework\Task\TaskRunner;
use WebFramework\Testing\InstantiationSweep;

$taskRunner = new TaskRunner('/path/to/app');
$taskRunner->build();

$sweep = new InstantiationSweep($taskRunner->get(ContainerInterface::class));

$result = $sweep->run(
    // The namespaces to check
    ['App\Actions', 'App\Core', 'App\Repository'],
    // Classes that are not meant to come from the container
    [App\Core\Money::class],
);

// In your test: an empty string means everything could be instantiated
$this->assertSame('', $result->describeFailures());
~~~

How it works:

- Classes are found through the PSR-4 map of Composer, so the namespaces have to be autoloaded by Composer.
- Interfaces, traits, enums and abstract classes are left out. List other classes that should not be tried, such as value objects, events and entities, in the second argument.
- Only the classes directly in a namespace are tried. Pass `true` as the third argument to include sub namespaces.
- The result has `instantiated`, `failed` (class and reason), `skipped` and `emptyNamespaces`. `isSuccessful()` is false when a class failed, and also when a namespace contained no classes at all, as that usually is a typo that would make the check pass without checking anything.

The helper does not depend on a test framework, so it works with PHPUnit, Codeception or anything else. You do not have to count classes: a new class is picked up by itself.

