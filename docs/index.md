Welcome to the WebFramework documentation. WebFramework is a companion framework to use on top of the Slim Framework, designed to provide a cohesive set of services around database management, ORM, caching, authentication, middleware, templating, translations, instrumentation, and more.

## Installation

### Skeleton Application

WebFramework is installed via Composer. You can install a skeleton application to get you started:

```bash
composer create-project avoutic/web-framework-skeleton
```

### Existing project

You can also add WebFramework to an existing project by installing it via Composer:

```bash
composer require avoutic/web-framework
```

You then probably want to add something like the following to your `composer.json` file, to make sure the core files are installed:

```json
    "scripts": {
        "post-install-cmd": [
            "php -r \"copy('vendor/avoutic/web-framework/htdocs/index.php', 'htdocs/index.php');\"",
            "mkdir -p scripts",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_init.php', 'scripts/db_init.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_update.php', 'scripts/db_update.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_version.php', 'scripts/db_version.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/sanity_check.php', 'scripts/sanity_check.php');\""
        ],
        "post-update-cmd": [
            "php -r \"copy('vendor/avoutic/web-framework/htdocs/index.php', 'htdocs/index.php');\"",
            "mkdir -p scripts",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_init.php', 'scripts/db_init.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_update.php', 'scripts/db_update.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/db_version.php', 'scripts/db_version.php');\"",
            "php -r \"copy('vendor/avoutic/web-framework/scripts/sanity_check.php', 'scripts/sanity_check.php');\""
        ]
    },
```
## Directory Structure

WebFramework applications typically have the following directory structure:

- **actions**: Contains files for each endpoint and related API functions.
- **config**: Contains the configuration files for the application.
- **db_scheme**: Contains the database migrations for the application.
- **definitions**: Contains the PHP-DI definitions for the application.
- **htdocs**: The location for the core `index.php` and external static files like images, CSS, and JavaScript.
- **scripts**: Contains scripts for tasks, migrations, and other automation tasks.
- **src**: Contains the core application/business logic and model files.
- **templates**: Contains the templates used by the actions.
- **tests**: Contains the tests for the application.
- **translations**: Contains the translation files for the application.

## Documentation Contents

This documentation is organized into several sections, each focusing on a specific aspect of WebFramework. Click on the links below to explore each section in detail:

- **[Installation Guide](https://github.com/avoutic/web-framework/blob/main/INSTALL.md)**: Instructions for installing WebFramework and setting up a base project.
- **[Migration Guide](https://github.com/avoutic/web-framework/blob/main/MIGRATE.md)**: Guidance on migrating between different versions of WebFramework.
- **[Configuration Management](./configuration.md)**: How to manage and access configuration settings in your application.
- **[Dependency Injection](./dependency-injection.md)**: How to use and configure dependency injection in WebFramework.
- **[Database Usage](./database.md)**: How to interact with the database, including executing queries and managing transactions.
- **[Database Migrations](./database-migrations.md)**: How to manage database schema changes using the DatabaseManager.
- **[Entities and Repositories](./entities-and-repositories.md)**: Understanding the Entity and Repository pattern in WebFramework.
- **[Creating New Entities](./new-entity.md)**: Step-by-step guide to creating new entities and repositories.
- **[Input Validation](./input-validation.md)**: How to add input validation to your actions using the InputValidationService.
- **[Routing](./routing.md)**: How to set up and manage routes in your WebFramework application.
- **[Middleware Management](./middlewares.md)**: How to define and use middleware to process requests and responses.
- **[Sanity Checks](./sanity-checks.md)**: How to use the sanity check system to ensure your application environment is correct.
- **[Tasks](./tasks.md)**: How to create and run tasks from the command line.
- **[Templating](./templating.md)**: How to use the Latte templating engine to render templates.
- **[Translations](./translations.md)**: How to deploy and configure multi-lingual support in your application.
- **[Emitting Responses](./emitting-responses.md)**: How to generate responses in actions using the ResponseEmitter or via exceptions.
- **[Caching](./caching.md)**: How to use caching to store and retrieve data efficiently.

## Getting Started

To get started with WebFramework, follow the [Installation Guide](https://github.com/avoutic/web-framework/blob/main/INSTALL.md) to set up your project. Once installed, explore the documentation to learn how to leverage the full capabilities of WebFramework in your application.

For any questions or further assistance, please refer to the documentation or reach out to the WebFramework community.

Happy coding!
