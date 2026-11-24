# Contributing

Thank you for your interest in contributing to this project. Contributions of all kinds are welcome.

## Supported Environment

- **Language:** PHP
- **Minimum version:** PHP 8.2

## Contribution Model

- Contributions are open to the public.
- Development happens on the `main` branch.
- All changes must be submitted via a pull request from a fork.
- Feature branches should be used for all work.

## License of Contributions

This project is licensed under the MIT License.  
By submitting a contribution, you agree that your work will be licensed under the same MIT License.

No Contributor License Agreement (CLA) is required.

## Documentation

This project uses [Zensical](https://zensical.org/) for documentation. You can run `zensical serve` to serve the documentation locally.

Please update the documentation in `README.md` and the `docs` directory, where applicable.

## Developing Against Local Checkouts

To try a change to the framework, or to one of its modules, in a real application, let Composer install the packages from your local checkouts. This works for all packages at once, and includes commits you have not pushed yet.

Assume the checkouts are next to each other, and your application is in `/path/to/app`:

```
/path/to/web-framework/web-framework
/path/to/web-framework/web-framework-mysql
/path/to/web-framework/web-framework-postmark
```

Add a path repository to the `composer.json` of the application:

```json
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/web-framework/web-framework*",
            "options": {
                "symlink": true,
                "versions": {
                    "avoutic/web-framework": "12.0.0",
                    "avoutic/web-framework-mysql": "12.0.0",
                    "avoutic/web-framework-postmark": "12.0.0"
                }
            }
        }
    ],
```

Then update the packages:

```sh
composer update "avoutic/*"
```

How this works:

- The `*` in the `url` matches every checkout that starts with `web-framework`. Composer uses the ones your application requires.
- A checkout has no version by itself. With `versions` you say which version each checkout counts as. Choose one that satisfies the version constraints in your application and in the modules, so nothing in `require` has to change.
- With `symlink`, `vendor/avoutic/web-framework` is a link to your checkout. A change in the checkout is active in the application right away, and you commit in the checkout as usual, with its own Git hooks.
- Composer reads the `composer.json` of the checkouts. After a change there, such as a new entry in `bin` or `autoload`, run `composer update "avoutic/*"` again.

Do not replace a directory in `vendor/` by a clone by hand. Composer does not notice that, so it keeps using the autoload and bin information of the released version.

This changes the `composer.json` and `composer.lock` of the application. Do not commit those changes. To go back to the released packages:

```sh
git checkout composer.json composer.lock
composer install
```

## Code Style

This project uses **php-cs-fixer** for code formatting. You can run `php-cs-fixer fix` to fix the code style.

## Testing Requirements

Test creation is required for:

- New features
- Bug fixes where tests reasonably prevent regressions

Contributors are encouraged to maintain or improve overall test coverage.

WebFramework uses Codeception for testing. The main test and analysis commands are:

```sh
vendor/bin/codecept run
```

## Static Analysis

The project uses PHPStan for static analysis. You can run:

```sh
vendor/bin/phpstan
```

Before submitting a pull request, ensure your code adheres to the existing php-cs-fixer configuration in the repository, and the static analysis and tests pass. The projects CI will run php-cs-fixer, codeception and phpstan to check the code style, unit tests and static analysis.