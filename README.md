Welcome to the WebFramework documentation. WebFramework is an add-on framework built on top of the Slim Framework, designed to provide a cohesive set of services around database management, caching, authentication, and middleware. Unlike traditional MVC frameworks, WebFramework follows the Model-Action-Responder (MAR) pattern, which influences where logic is located within your application.

## Overview

WebFramework applications typically have the following directory structure:

- **actions**: Contains files for each endpoint and related API functions.
- **config**: Contains the configuration files for the application.
- **migrations**: Contains the database migrations for the application.
- **definitions**: Contains the PHP-DI definitions for the application.
- **htdocs**: The location for the core `index.php` and external static files like images, CSS, and JavaScript.
- **scripts**: Contains scripts for tasks, migrations, and other automation tasks.
- **src**: Contains the core application/business logic and model files.
- **templates**: Contains the templates used by the actions.
- **tests**: Contains the tests for the application.
- **translations**: Contains the translation files for the application.

## Documentation Contents

This documentation is organized into several sections, each focusing on a specific aspect of WebFramework. Click on the links below to explore each section in detail:

- **[Installation Guide](INSTALL.md)**: Instructions for installing WebFramework and setting up a base project.
- **[Migration Guide](MIGRATE.md)**: Guidance on migrating between different versions of WebFramework.
- **[Configuration Management](docs/configuration.md)**: How to manage and access configuration settings in your application.
- **[Dependency Injection](docs/dependency-injection.md)**: How to use and configure dependency injection in WebFramework.
- **[Database Usage](docs/database.md)**: How to interact with the database, including executing queries and managing transactions.
- **[Database Migrations](docs/database-migrations.md)**: How to manage database schema changes using the DatabaseManager.
- **[Entities and Repositories](docs/entities-and-repositories.md)**: Understanding the Entity and Repository pattern in WebFramework.
- **[Creating New Entities](docs/new-entity.md)**: Step-by-step guide to creating new entities and repositories.
- **[Input Validation](docs/input-validation.md)**: How to add input validation to your actions using the InputValidationService.
- **[Routing](docs/routing.md)**: How to set up and manage routes in your WebFramework application.
- **[Middleware Management](docs/middlewares.md)**: How to define and use middleware to process requests and responses.
- **[Sanity Checks](docs/sanity-checks.md)**: How to use the sanity check system to ensure your application environment is correct.
- **[Tasks](docs/tasks.md)**: How to create and run tasks from the command line.
- **[Templating](docs/templating.md)**: How to use the Latte templating engine to render templates.
- **[Translations](docs/translations.md)**: How to deploy and configure multi-lingual support in your application.
- **[Emitting Responses](docs/emitting-responses.md)**: How to generate responses in actions using the ResponseEmitter or via exceptions.
- **[Caching](docs/caching.md)**: How to use caching to store and retrieve data efficiently.
- **[Queueing](docs/queueing.md)**: How to queue Jobs and handle them asynchronously.
- **[Logging](docs/logging.md)**: How to configure log channels and route log output.
- **[Event Handling](docs/events.md)**: How to trigger and handle Events with EventListeners.

## Getting Started

To get started with WebFramework, follow the [Installation Guide](INSTALL.md) to set up your project. Once installed, explore the documentation to learn how to leverage the full capabilities of WebFramework in your application.

For any questions or further assistance, please refer to the documentation or reach out to the WebFramework community.

Happy coding!

## Modules

Opinionated choices are provided with external modules:

- **avoutic/web-framework/browserless**: Browserless module
- **avoutic/web-framework-mysql**: MySql database module
- **avoutic/web-framework-postmark**: Postmark mail module
- **avoutic/web-framework-redis**: Redis caching and queueing module
- **avoutic/web-framework-sentry**: Sentry.io instrumentation module
- **avoutic/web-framework-stripe**: Stripe wrapper
