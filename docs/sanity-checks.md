# Sanity Check System

This document provides a guide for developers on the sanity check system in WebFramework. Sanity checks are used to ensure that the application environment and configuration are correct and that the application is ready to run.

## Overview

The sanity check system in WebFramework is designed to perform various checks on the application environment and configuration. It is managed by the `SanityCheckRunner` and executed as part of the `BootstrapService`.

## When Sanity Checks Are Run

Sanity checks are typically run during the bootstrap process of the application. They are executed if the `runSanityChecks` flag is set to `true` in the `BootstrapService`. This is usually the case in development and testing environments.

### Debug Environment

Sanity checks are run by default in debug environments. You can control whether sanity checks are run by setting the `runSanityChecks` flag in the `BootstrapService`.

### Production Environment

In a production environment, sanity checks are only run once on each new 'commit' that is seen in the BuildInfo.

## Triggering Sanity Checks Manually

You can manually trigger sanity checks by running the `sanity_check.php` script. This script initializes the application and executes the `SanityCheckTask`.

#### Example Command

~~~bash
php scripts/sanity_check.php
~~~

This command will execute all registered sanity checks and output the results to the console.

## Adding Your Own Sanity Checks

To add your own sanity checks, you need to create a class that implements the `SanityCheckModule` interface. This interface defines the contract for sanity check modules.

### Example Sanity Check Module

~~~php
<?php

namespace App\SanityCheck;

use WebFramework\SanityCheck\SanityCheckBase;

class CustomSanityCheck extends SanityCheckBase
{
    public function performChecks(): bool
    {
        // Implement your custom checks here
        // Return true if all checks pass, false otherwise
        return true;
    }
}
~~~


## Configuring Sanity Checks

Sanity checks are enabled and configured in the application's configuration file under the `sanity_check_modules` key. This key contains an associative array with fully qualified class names as keys and their respective configuration arrays as values.

### Example Configuration

~~~php
return [
    // Other configuration settings...

    'sanity_check_modules' => [
        \WebFramework\SanityCheck\RequiredCoreConfig::class => [],
        \WebFramework\SanityCheck\DatabaseCompatibility::class => [],
        \App\SanityCheck\CustomSanityCheck::class => [],
    ],
];
~~~

## Existing Sanity Check Modules

Here is a description of the existing sanity check modules and their configuration:

### RequiredCoreConfig

- **Purpose**: Checks for required core configuration options.
- **Configuration**: No additional configuration is required.

### DatabaseCompatibility

- **Purpose**: Checks for database compatibility, including version checks.
- **Configuration**: No additional configuration is required.

### RequiredAuth

- **Purpose**: Checks for the presence of required authentication files.
- **Configuration**: An array of filenames to check for.

#### Example

~~~php
\WebFramework\SanityCheck\RequiredAuth::class => [
    'auth_file_1.php',
    'auth_file_2.php',
],
~~~
