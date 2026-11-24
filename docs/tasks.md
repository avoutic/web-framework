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

Tasks are run with the `framework` command line tool, which Composer installs as `vendor/bin/framework`:

~~~bash
# Show the available commands
vendor/bin/framework

# Show the help of a command
vendor/bin/framework help db:migrate

# Run a command
vendor/bin/framework db:migrate
~~~

The tool finds your application through Composer, so it works from any directory.

To make a task of your own available as a command, extend `ConsoleTask` and register it under the `console_tasks` key in your configuration. Any other task that implements the `Task` interface can be run with the `task:run` command, see below.

### Example: Running a Task from Your Own Script

You can also run a task from a script of your own:

~~~php
<?php

use App\Task\CustomTask;
use WebFramework\Task\TaskRunner;

require_once __DIR__.'/../vendor/autoload.php';

$taskRunner = new TaskRunner(__DIR__.'/..');
$taskRunner->build();

$taskRunner->execute(CustomTask::class);
~~~

This script initializes the `TaskRunner`, builds the application environment, and executes the `CustomTask`.

### Running a Command for Another Environment

The `framework` command line tool uses the same configuration as the application. Set the `APP_ENV` environment variable to run a command with the configuration of another environment, for example to migrate a test database:

~~~bash
APP_ENV=testing vendor/bin/framework db:migrate
~~~

With `APP_ENV=testing`:

- `.env.testing` is used instead of `.env`, if it exists.
- `/config/config_testing.php` is loaded on top of `/config/config.php`, if it exists.

Put what differs for that environment, such as the database to use, in those files. `APP_ENV` has to be set in the environment of the command, as above: setting it in a `.env` file is too late. See [Configuration](configuration.md) for details.

### Failing Tasks

A command that is run through the `framework` command line tool and fails with an exception is handled the same way as an unhandled exception in a web request: it is logged to the default and exception log channels and reported through the configured `ReportFunction`. The message is printed and the exit code is `1`. This way a failing scheduled task does not go unnoticed. Wrong arguments are not reported, they only show the usage.

A script of your own that calls `TaskRunner::execute()` directly, like the example above, does not go through this handling. Catch exceptions there yourself and pass them to `DebugService::reportException()`.

## Existing Tasks

The WebFramework includes several predefined tasks for common operations. Here are the existing tasks and their purposes:

### SlimAppTask

- **Purpose**: Initializes and runs the Slim application.
- **Script**: `public/index.php`. It is run by the web server, not from the command line.

### Commands

The `framework` command line tool comes with these commands:

| Command | Purpose |
|---|---|
| `cache:clear` | Clear the entire cache |
| `config:show [path]` | Display the loaded configuration |
| `definitions:show [key]` | Display the loaded definition files and their contents |
| `db:migrate` | Run pending database migrations |
| `db:rollback` | Roll back the last batch of database migrations, after a typed confirmation |
| `db:status` | Show database migration status |
| `db:make` | Generate a new migration file |
| `db:convert-from-scheme` | Migrate from the old db_scheme system to the migrations system |
| `db:convert-production` | Convert a production database to the migrations system |
| `queue:list` | List all queues |
| `queue:worker` | Run a queue worker |
| `queue:clear` | Clear a task queue |
| `sanity:check` | Run sanity checks on the application |
| `task:run` | Run a task class, see below |
| `verification-codes:cleanup` | Clean up expired and used verification codes |

Run `vendor/bin/framework help <command>` for the arguments and options of a command.

### TaskRunnerTask

- **Purpose**: Executes a custom task class from the command line.
- **Command**: `task:run`
- **Usage**: Run this command to execute any task that implements the `Task` interface.

#### Options

- `--continuous`: Run the task continuously in a loop
- `--delay <secs>`: The delay between continuous runs in seconds (default: 1)
- `--max-runtime <secs>`: The maximum runtime in seconds for continuous execution
- `--attempts <num>`: The maximum number of attempts if the task fails (default: 1, no retries)
- `--backoff <secs>`: The backoff delay in seconds between retries (default: 1). The actual delay is multiplied by the attempt number (exponential backoff)

#### Examples

```bash
# Run a task once
vendor/bin/framework task:run App\\Task\\MyTask

# Run a task with retry logic (3 attempts with 2 second base backoff)
vendor/bin/framework task:run --attempts 3 --backoff 2 App\\Task\\MyTask

# Run a task continuously with retry logic
vendor/bin/framework task:run --continuous --delay 60 --attempts 3 --backoff 5 App\\Task\\MyTask
```

When using retry logic, if a task throws an exception, it will be retried up to the specified number of attempts. The delay between retries follows an exponential backoff pattern: `backoff * attempt_number`. For example, with `--backoff 2` and `--attempts 3`, the delays will be 2 seconds (after attempt 1), 4 seconds (after attempt 2), and then the final exception will be thrown if attempt 3 also fails.