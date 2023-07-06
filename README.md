WebFramework is add-on framework on top of Slim Framework, to provide a cohesive set of services around Database, Caching, Authentication and Middlewares.

WebFramework is not based on Model-View-Controller (MVC) but on Model-Action-Responder (MAR) instead. A big difference with other frameworks is therefore where which logic is located.

In general WebFramework apps have the following directories in their root:

* **actions**: A file for each endpoint and each set of API functions that belong together.
* **htdocs**: The location for the core *index.php* and external static files like images, CSS and javascript files.
* **src**: The core app/business logic and model files.
* **templates**: The templates used by the actions

## Installation

Installing WebFramework with composer:

```
composer require avoutic/web-framework
```

You can also start with a base project:

```
composer create-project avoutic/web-framework-example
```

More details can be found in the [Installation guide](INSTALL.md).

## Migration

If you are migrating between different versions of WebFramework, be sure to check out the [Migration guide](MIGRATE.md) for instructions on any big changes.
