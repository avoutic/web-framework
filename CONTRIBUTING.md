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