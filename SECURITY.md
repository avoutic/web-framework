# Security Policy

## Supported Versions

Only the latest released version of this project is supported with security updates.  
Older versions do **not** receive security fixes.

## Reporting a Vulnerability

If you discover a security vulnerability, **do not report it publicly**.

Instead, report it using [**GitHub Security Advisories**](https://github.com/avoutic/web-framework/security/advisories) for this repository.

This allows responsible disclosure and coordinated remediation.

## Disclosure Policy

- Public disclosure of security issues before coordination is not permitted.
- Please allow the maintainer to investigate and address the issue before sharing details publicly.

This project is a base web framework, and vulnerabilities may affect a large number of downstream applications.

## Response Timeline

- **Initial response:** within 72 hours
- **Remediation:** depends on severity and complexity. The goal is to resolve or mitigate the issue within 72 hours after the initial response whenever possible.

## Security Releases

- Security fixes are distributed via **regular releases**.
- [Semantic Versioning](https://semver.org/) is followed.
- Dependabot is enabled to assist with third-party dependency updates.

## Third-Party Dependencies

This project depends on external libraries, including (but not limited to):

- [Slim Framework](https://www.slimframework.com/)
- [PHP-DI](https://php-di.org/)
- [Latte templating engine](https://latte.nette.org/)

Security advisories affecting these dependencies may also impact this project, so please inform the maintainer if you are aware of any.

## Credit

Security reporters may be acknowledged in release notes or advisories if they wish.

## Maintainer Responsibility

The project maintainer is responsible for:

- Security triage
- Vulnerability assessment
- Coordinating fixes and releases