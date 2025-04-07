# Tasks

This document provides a guide for developers on how to create and run tasks in the WebFramework. Tasks are used to perform specific operations, such as database initialization or updates, and can be executed from the command line.

## Overview

Tasks in WebFramework implement the `Task`, which defines a single method, `execute()`. This method contains the logic for the task. Tasks are executed using the `TaskRunner`, which sets up the application environment and runs the specified task.

## Creating Custom Tasks

To create a custom task, you need to implement the `Task` and define the `execute()` method. This method should contain the logic for your task.

### Example: Custom Task

~~~php
<?php

namespace App\Task;

use WebFramework\Core\Task;

class CustomTask implements Task
{
    public function execute(): void
    {
        // Task logic goes here
        echo "Running custom task...";
    }
}
~~~

In this example, the `CustomTask` class implements the `Task` and defines the `execute()` method, which contains the task logic.

## Running Tasks from the Command Line

Tasks can be executed from the command line using PHP scripts. These scripts initialize the `TaskRunner` and execute the specified task.

### Example: Running a Task

To run a task from the command line, create a script similar to the following:

~~~php
<?php

use WebFramework\Core\TaskRunner;
use App\Task\CustomTask;

require_once __DIR__.'/../vendor/autoload.php';

$taskRunner = new TaskRunner(__DIR__.'/..');
$taskRunner->build();

$taskRunner->execute(CustomTask::class);
~~~

This script initializes the `TaskRunner`, builds the application environment, and executes the `CustomTask`.

## Existing Tasks

The WebFramework includes several predefined tasks for common operations. Here are the existing tasks and their purposes:

### SlimAppTask

- **Purpose**: Initializes and runs the Slim application.
- **Script**: `htdocs/index.php`
- **Script**: Typically run as part of the web server setup, not directly from the command line.

### DbInitTask

- **Purpose**: Initializes the database schema.
- **Script**: `scripts/db_init.php`
- **Usage**: Run this script to set up the initial database schema.

### DbUpdateTask

- **Purpose**: Updates the database schema to the latest version.
- **Script**: `scripts/db_update.php`
- **Usage**: Run this script to apply database migrations and update the schema.

### DbVersionTask

- **Purpose**: Displays the current database version.
- **Script**: `scripts/db_version.php`
- **Usage**: Run this script to check the current version of the database schema.

### SanityCheckTask

- **Purpose**: Runs sanity checks on the application.
- **Script**: `scripts/sanity_check.php`
- **Usage**: Run this script to perform sanity checks and ensure the application environment is correct.