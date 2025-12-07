# WebFramework

[![Build Status](https://img.shields.io/github/check-runs/avoutic/web-framework/main)](https://github.com/avoutic/web-framework/actions/workflows/ci.yml?query=branch:main)
[![Latest Tag](https://img.shields.io/github/v/tag/avoutic/web-framework)](https://packagist.org/packages/avoutic/web-framework)
[![License](https://img.shields.io/github/license/avoutic/web-framework)](https://packagist.org/packages/avoutic/web-framework)
[![GitHub top language](https://img.shields.io/github/languages/top/avoutic/web-framework)](https://github.com/avoutic/web-framework)
[![Coverage Status](https://coveralls.io/repos/github/avoutic/web-framework/badge.svg)](https://coveralls.io/github/avoutic/web-framework)

**A lightweight, secure-by-default PHP microframework built on Slim – providing Laravel-like features (ORM, authentication, migrations, caching) without the bloat.**

WebFramework is a companion framework that extends the [Slim Framework](https://www.slimframework.com/) with a cohesive set of services for database management, ORM, caching, authentication, middleware, templating, and more. Perfect for developers who love Slim's simplicity but need full-featured components for building modern PHP applications and REST APIs.

## Why WebFramework?

- **Lightweight PHP Framework**: Built on Slim's minimal foundation, WebFramework adds only what you need
- **Laravel Alternative**: Get Laravel-like features (ORM, migrations, auth) without the heavy footprint
- **Slim Framework ORM**: Built-in Entity/Repository pattern for database operations
- **Slim Framework Authentication**: Complete user management flows (registration, login, password reset, email verification)
- **Secure-by-Default**: Security best practices built into the framework
- **PSR-7/PSR-15 Compliant**: Modern PHP standards throughout
- **Perfect for REST APIs**: Ideal for building lightweight PHP APIs and microservices

### WebFramework vs Laravel vs Slim

| Feature | Slim | WebFramework | Laravel |
|---------|------|--------------|---------|
| Learning Curve | Low | Low | Medium-High |
| Footprint | Minimal | Lightweight | Heavy |
| ORM | ❌ Manual | ✅ Built-in | ✅ Eloquent |
| Migrations | ❌ Manual | ✅ Built-in | ✅ Built-in |
| Authentication | ❌ Manual | ✅ Built-in | ✅ Built-in |
| Caching | ❌ Manual | ✅ Built-in | ✅ Built-in |
| Queueing | ❌ Manual | ✅ Built-in | ✅ Built-in |
| Best For | APIs, Microservices | Small-Medium Apps, APIs | Large Applications |

**Use WebFramework if**: You want Slim's simplicity with Laravel's convenience, or you're building a small-to-medium application that doesn't need Laravel's full feature set.

## Quick Start

### Installation

Install WebFramework via Composer:

```bash
composer require avoutic/web-framework
```

Or start with a skeleton project:

```bash
composer create-project avoutic/web-framework-skeleton
```

See the [Installation Guide](INSTALL.md) for detailed setup instructions.

### Example: Building a REST API

```php
<?php
// actions/GetUser.php
namespace App\Actions;

use WebFramework\Repository\UserRepository;

class GetUser
{
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response, array $routeParams): ResponseInterface
    {
        $user = $this->userRepository->find($routeParams['id']);
        return $response->withJson($user);
    }
}
```

Check out the [example application](https://github.com/avoutic/web-framework-example) for a complete working demo.

## Features

- **Entity/Repository Pattern**: Built-in ORM with type-safe entities and repositories
- **Database Migrations**: Version-controlled schema management
- **Authentication & Authorization**: Complete user management with email verification
- **Input Validation**: Type-safe request validation
- **Middleware System**: PSR-15 compliant middleware pipeline
- **Templating**: Latte templating engine integration
- **Caching**: Built-in caching support (Redis module available)
- **Queue System**: Asynchronous job processing
- **Event System**: Event-driven architecture
- **Dependency Injection**: PHP-DI integration
- **Multi-language Support**: Translation system
- **Security**: Built-in CSRF protection, secure sessions, and more

## Overview

WebFramework applications typically have the following directory structure:

- **actions**: Contains files for each endpoint and related API functions.
- **config**: Contains the configuration files for the application.
- **migrations**: Contains the database migrations for the application.
- **definitions**: Contains the PHP-DI definitions for the application.
- **public**: The location for the core `index.php` and external static files like images, CSS, and JavaScript.
- **scripts**: Contains scripts for tasks, migrations, and other automation tasks.
- **src**: Contains the core application/business logic and model files.
- **templates**: Contains the templates used by the actions.
- **tests**: Contains the tests for the application.
- **translations**: Contains the translation files for the application.

## Documentation

Complete documentation is available at **[web-framework.com](https://web-framework.com)**.

### Key Topics

- **[Installation Guide](INSTALL.md)**: Instructions for installing WebFramework and setting up a base project
- **[Migration Guide](MIGRATE.md)**: Guidance on migrating between different versions of WebFramework
- **[Configuration Management](docs/configuration.md)**: How to manage and access configuration settings
- **[Dependency Injection](docs/dependency-injection.md)**: How to use and configure dependency injection
- **[Database Usage](docs/database.md)**: How to interact with the database, including queries and transactions
- **[Database Migrations](docs/database-migrations.md)**: How to manage database schema changes
- **[Entities and Repositories](docs/entities-and-repositories.md)**: Understanding the Entity and Repository pattern
- **[Creating New Entities](docs/new-entity.md)**: Step-by-step guide to creating new entities and repositories
- **[Input Validation](docs/input-validation.md)**: How to add input validation to your actions
- **[Routing](docs/routing.md)**: How to set up and manage routes
- **[Middleware Management](docs/middlewares.md)**: How to define and use middleware
- **[Templating](docs/templating.md)**: How to use the Latte templating engine
- **[Caching](docs/caching.md)**: How to use caching to store and retrieve data
- **[Queueing](docs/queueing.md)**: How to queue Jobs and handle them asynchronously
- **[Event Handling](docs/events.md)**: How to trigger and handle Events with EventListeners

### Common Use Cases

- **Building REST APIs**: WebFramework is perfect for creating lightweight PHP APIs with built-in ORM and authentication
- **Adding ORM to Slim**: If you're using Slim and need database management, WebFramework adds a complete Entity/Repository system
- **Laravel Alternative**: Need Laravel features without the complexity? WebFramework provides migrations, auth, and more on a Slim foundation
- **Microservices**: Build small, focused services with WebFramework's minimal footprint

## Modules

Extend WebFramework with optional modules for specific needs:

- **[avoutic/web-framework-mysql](https://packagist.org/packages/avoutic/web-framework-mysql)**: MySQL database module
- **[avoutic/web-framework-redis](https://packagist.org/packages/avoutic/web-framework-redis)**: Redis caching and queueing module
- **[avoutic/web-framework-postmark](https://packagist.org/packages/avoutic/web-framework-postmark)**: Postmark mail module
- **[avoutic/web-framework-sentry](https://packagist.org/packages/avoutic/web-framework-sentry)**: Sentry.io instrumentation module
- **[avoutic/web-framework-stripe](https://packagist.org/packages/avoutic/web-framework-stripe)**: Stripe payment integration
- **[avoutic/web-framework/browserless](https://packagist.org/packages/avoutic/web-framework/browserless)**: Browserless automation module

See the [modules documentation](https://web-framework.com/modules) for details.

## Requirements

- PHP 8.2 or higher
- Composer
- PSR-7/PSR-15 compatible web server

## License

WebFramework is open-source software licensed under the [MIT license](LICENSE).

## Contributing

Contributions are welcome! Whether you're fixing bugs, adding features, or improving documentation, your help makes WebFramework better for everyone.

Before opening an issue or pull request, please read [CONTRIBUTING.md](CONTRIBUTING.md).

- **Report issues:** Found a bug or have a small improvement? [Open an issue](https://github.com/avoutic/web-framework/issues).
- **Design first for larger changes:** For larger features or architectural changes, start with a “design” issue so we can discuss the approach before implementation.
- **Workflow:** Fork the repository, create a feature branch from `main`, then open a pull request back to `main`.
- **Improve Documentation**: Help make the docs better by submitting improvements
- **Share Your Experience**: Built something cool with WebFramework? Let us know!
- **Code style:** Use the existing `php-cs-fixer` configuration before submitting.
- **Tests:** For new features and bug fixes, add tests where they help prevent regressions and run:
  ```sh
  vendor/bin/phpstan
  vendor/bin/codecept run
  ```

By submitting a contribution, you agree that your work is licensed under the MIT License, in line with the project’s license.

This project follows the [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/1/code_of_conduct/).

## Security

If you discover a security vulnerability, **do not** open a public issue.

Instead, report it via [GitHub Security Advisories](https://github.com/avoutic/web-framework/security/advisories) so we can coordinate a fix.

For full details, including response timelines and supported versions, see [SECURITY.md](SECURITY.md).

## Community & Support

- **Documentation**: [web-framework.com](https://web-framework.com)
- **GitHub**: [github.com/avoutic/web-framework](https://github.com/avoutic/web-framework)
- **Example App**: [github.com/avoutic/web-framework-example](https://github.com/avoutic/web-framework-example)
- **Packagist**: [packagist.org/packages/avoutic/web-framework](https://packagist.org/packages/avoutic/web-framework)

---

**Made with ❤️ for the PHP community**
