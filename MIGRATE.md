# v12 release

## Breaking changes

* `LoginExtensionInterface` has a new method `preAuthenticate(Request $request, User $user): bool`. Implementations that do not extend `NullLoginExtension` must add it. Return `true` to keep the current behavior.
* `LoginService` has a new constructor parameter `LoginExtensionInterface $loginExtension`. Autowired definitions need no change.
* The `LoginVerify` and `RegisterVerify` actions take `LoginService` instead of `AuthenticationService` in their constructor. Subclasses must be adapted. Subclasses that only existed to add a check before authentication can be replaced by `preAuthenticate()`.
* `UserLoggedIn` is now also dispatched after login verification (`LoginVerify`) and registration verification (`RegisterVerify`). Apps that dispatched it themselves on those paths, or did login bookkeeping elsewhere to work around the gap, must remove that to avoid duplicate handling.
* `RegisterExtensionInterface::postVerify()` is now called exactly once per user, on first verification, whichever flow caused it. It is no longer called when an already verified user redeems a register code (for example a stale resend). It is now also called from `LoginVerify` when a user is verified for the first time through a login code, with the same data from `getAfterVerifyData()`. It runs before the login is completed, so also when `preAuthenticate()` refuses the login.
* `LoginVerify` has a new constructor parameter `RegisterExtensionInterface $registerExtension`.
* `RegisterService` and `UserVerificationService` have a new constructor parameter `StoredUserValuesService $storedUserValuesService`. Autowired definitions need no change.
* The data from `RegisterExtensionInterface::getAfterVerifyData()` is now stored as JSON with the user (stored user value `registration.after_verify_data`) until the user is verified for the first time, so it must be JSON serializable. Unlike verification codes, it is not removed by `CleanupVerificationCodesTask`; it stays until the user verifies. Users that registered before the upgrade and are still unverified get the data from their register code when they verify through `RegisterVerify`, and an empty array when they verify through `LoginVerify`.
* Accounts now have a status, decided by the new `Security\AccountStatusChecker` interface (`isEnabled(User $user): bool`). The default binding, `DefaultAccountStatusChecker`, treats an account with an empty password hash as disabled, following the existing convention for blocked accounts. A disabled account:
    * cannot log in. `LoginService::validate()`, `LoginService::authenticate()` and `LoginService::completeLogin()` throw `AccountDisabledException`. Custom login actions or endpoints that call them must catch this exception.
    * loses all its sessions on its next request. `AuthenticationMiddleware` invalidates them and treats the request as not authenticated.
    * cannot reset its password. `ResetPasswordService::sendPasswordResetMail()` and `ResetPasswordService::handleData()` throw `AccountDisabledException`. This closes the hole where a blocked account could unblock itself with a password reset.
    * is not sent new login or password reset codes by `VerifyResend`. Registration codes are still sent, so an account that is disabled until approval can verify its email address.
    * cannot change its password or email. `ChangePasswordService::changePassword()`, `ChangeEmailService::sendChangeEmailVerify()` and `ChangeEmailService::handleData()` throw `AccountDisabledException`.
* If your application has accounts with an empty password hash that must stay usable (for example accounts that get their first password through the password reset flow), bind `Security\AccountStatusChecker` to `Security\NullAccountStatusChecker` or to your own implementation.
* `LoginService`, `ResetPasswordService`, `ChangePasswordService`, `ChangeEmailService`, `AuthenticationMiddleware` and the `VerifyResend` action have a new constructor parameter `AccountStatusChecker $accountStatusChecker`. Autowired definitions need no change.
* `LoginService::validate()` no longer checks whether the user is verified and no longer throws `UserVerificationRequiredException`. That check now only happens in `LoginService::authenticate()`. In the `Login` action this means `LoginExtensionInterface::customValueCheck()` now also runs for unverified users, before a verification code is sent. Code that calls `validate()` on its own and relied on it to detect unverified users must call `authenticate()` or check `User::isVerified()` itself.
* `LoginService::authenticate()` no longer takes a password and no longer checks it: the signature is now `authenticate(Request $request, User $user)`. The password is checked once, in `LoginService::validate()`. Callers must call `validate()` first, as the `Login` action does.
* `CheckPasswordService::checkPassword()` no longer records the last login or resets the failed login counter on a correct password. It only saves the user when it migrated the password hash. A wrong password still increments the failed login counter. The last login is now recorded, and the failed login counter reset, in `LoginService::completeLogin()`, when the session is created. As a result logins that are refused or stop at the verification step no longer count as a login, and logins through `LoginVerify` and `RegisterVerify` now do. Code that calls `checkPassword()` on its own and relied on it to record the login must do that itself.
* Change detection in `RepositoryCore::save()` is now exact. Before, a column that was NULL when the entity was loaded was written back as NULL on every `save()`, which could overwrite a concurrent change to that column. A change between NULL, an empty string, `0` and `false` was not detected at all, and the same changes were written again on every later `save()` of the same instance. Now:
    * NULL columns are part of the snapshot taken at load, so they are only written when they actually changed.
    * Values are compared strictly, so for example changing a field from `''` to `null` is saved.
    * The snapshot is refreshed after an insert or update, so a second `save()` without changes does not query the database.
    * `Entity::getOriginalValues()` returns the values as the entity holds them (for example `5` for an `int` property), instead of the raw values from the database driver (`'5'`).
    * Code that relied on `save()` rewriting unchanged fields, for example to undo a change made by a raw query, must set the field explicitly or run its own query.
    * A NULL value for a property that cannot hold NULL still leaves the property at its default, as before.
* The field lists of an entity (`$baseFields`, `$privateFields`, `$fillableFields` and `$additionalIdFields`) are now merged with the lists of its parent entities, instead of replacing them. An entity that extends another entity, such as `User`, only has to declare its own fields, and keeps working when the framework adds a field to the parent. The fields of the parent come first. Listing the parent fields again is still allowed; duplicates are removed. Consequences:
    * An entity can no longer drop a field of its parent by leaving it out of its own list. In particular a private field of the parent, such as `solid_password` on `User`, stays private.
    * `EntityCore` and `RepositoryCore` read the lists through `getBaseFields()`, `getPrivateFields()`, `getFillableFields()` and `getAdditionalIdFields()`. Code that reads `static::$baseFields` (or one of the other lists) directly only sees the list declared on that class; use the getters to get the merged list.
* The `Database` interface has a new method `transaction(callable $callback): mixed`. Other implementations of `Database` must add it; they can use `Database\TransactionTrait`.
* `TransactionMiddleware` now rolls back the transaction when the handler throws, and throws the exception again. Before, the transaction and its nesting depth were left open, which in a long-running process meant that later transactions were never committed. This applies to every exception, also the ones that result in a normal response further up, such as `RedirectException` and `HttpNotFoundException`. To keep what was written, return a response instead of throwing.
* The queued mail handlers (`RawMailJobHandler` and `TemplateMailJobHandler`) no longer retry a mail when the backend returns `MailBackend::INACTIVE_ADDRESS` (`'inactive_address'`). They log a warning and complete the job. Before, the job failed and was retried until its attempts ran out.
* Error reports no longer contain credentials. Before, only the `Cookie` header was removed, and the server params, query, post and JSON inputs were reported in full. That put session cookies (as `HTTP_COOKIE`), `Authorization` headers, `PHP_AUTH_PW`, environment variables and posted passwords in error mails and logs. Now:
    * Request headers whose name contains `authorization`, `cookie`, `token`, `secret` or `key` have their value replaced by `scrubbed`. The header itself stays in the report, where the `Cookie` header used to be removed.
    * Query, post and JSON inputs whose key contains `password`, `passwd`, `secret`, `token`, `authorization` or `code` have their value replaced by `scrubbed`, at any depth. This also catches keys such as `postal_code`.
    * A JSON body that cannot be parsed is reported as its size only.
    * Only an allow list of server params is reported. Everything else, including the environment of the process, is left out.
    * Query parameters in `REQUEST_URI`, `QUERY_STRING` and the referer are scrubbed like inputs.
    * The three lists are configurable as `error_report.scrub_headers`, `error_report.scrub_inputs` and `error_report.server_params`. Add the names of your own secret fields and headers to them.
    * `DebugService` has three new optional constructor parameters for these lists.
* `AdminUserMiddleware` now throws `HttpUnauthorizedException` (401) for a request without an authenticated user. It used to throw a `RuntimeException`, which resulted in a 500. The `RuntimeException` remains for the case where `AuthenticationMiddleware` has not run.
* `LoginService::authenticate()` can now throw `LoginRefusedException` when `preAuthenticate()` refuses the login. Custom login actions or endpoints that call it must catch this exception.

## New features

* Introduce `LoginService::completeLogin()` as the single place where a login results in a session. It calls `LoginExtensionInterface::preAuthenticate()`, authenticates the user and dispatches `UserLoggedIn`. It throws `LoginRefusedException` when the extension refuses the login.
* `UserVerificationService::handleData()` returns an extra `first_verification` flag that tells whether this call verified the user for the first time, and `post_verify_data` with the registration after verify data on first verification.
* Introduce `Security\AccountStatusChecker` with `DefaultAccountStatusChecker` and `NullAccountStatusChecker`, and `AccountDisabledException`. Use it for anything that is a property of the account (disabled, suspended, pending approval). Use `LoginExtensionInterface::preAuthenticate()` for checks that depend on the request.
* Introduce `Exception\MailException` (a `\RuntimeException`) for failures of a mail backend, so callers do not depend on the exceptions of a specific backend. It is thrown by mail modules that support it, such as `web-framework-postmark` for WebFramework 12.
* Introduce the constant `MailBackend::INACTIVE_ADDRESS` for the result of a permanently refused recipient.
* Introduce `Mail\LoggingMailService`, which writes mails to the log instead of sending them, so mailed content such as verification codes is reachable on a development machine. It logs secrets and is for development only.
* Introduce `Database::transaction()` to run a callback in a transaction, with commit on return and rollback on an exception. Calls can be nested.
* Introduce `EntityCore::getPrivateFields()` and `EntityCore::getFillableFields()`.
* Introduce `Exception\DatabaseException` (a `\RuntimeException`) for failed database operations. It carries the driver error number, the SQLSTATE, the driver error message and the previous exception, and has the helpers `isDuplicateKey()`, `isLockTimeout()` and `isDeadlock()`. The `Database` interface now documents this exception. It is thrown by database modules that support it, such as `web-framework-mysql` for WebFramework 12.
* Introduce `UserCodeService::getRedeemableCode(string $guid, string $action)`, which returns a verification code that is ready to be redeemed for an action without consuming it, or throws `CodeVerificationException`. `UserVerificationService::handleData()`, `ResetPasswordService::handleData()` and `ChangeEmailService::handleData()` now use it instead of each carrying a copy of the checks. Custom actions can use it to check a code before acting on it.
* New config option `security.email_verification.resend_cooldown_seconds` (default: 60) for the time before `VerifyResend` sends a new code. It replaces the hardcoded 60 seconds.
* New translation keys `login.refused`, `login.refused_extra`, `login.account_disabled` and `login.account_disabled_extra`.

# v11 release

## v11.7

### New features

* Introduce `add_foreign_key` and `drop_foreign_key` and `modify_foreign_key` migration actions.

## v11.6

### New features

* Introduce `findOneByOrFail()` method to `RepositoryCore`.

## v11.5

### New features

* Introduce `findOrFail()`, `inRandomOrder()` and `selectOne()` method to `RepositoryQuery`.
* Introduce `findOrFail()` method to `RepositoryCore`.
* Introduce `Database\Lock` class to manage database locks.
* Have `count()` method in `RepositoryQuery` accept a `column` parameter to count distinct values in a column.

## v11.4

### New features

* `Database\Database` now supports `affectedRows()` method to get the number of affected rows by the last query.
* `Repository\RepositoryQuery` provides a fluent interface for building queries, supporting filtering, limit, offset, ordering, grouping, distinct, aggregate functions, exists, conditional clauses, locking for update, chunking, deleting, updating, pagination and debugging.
* Database filtering now supports filtering with `Column` objects, 'OR' conditions and multiple conditions on the same field.
* Introduce more methods to `RepositoryCore`: `findOneBy()`, `findBy()`, `count()`, `find()`, deprecated `getObject()`, `getObjects()`, `getObjectById()`, and `countObjects()`.

## v11.3.1

### Bug fixes

* `SanityCheck\SanityCheckRunner` now catches exceptions when setting the checked value to prevent race conditions.

## v11.3

### New features

* `Task\QueueWorker` now supports a `--empty-sleep` option to control the number of seconds to sleep when there are no jobs.
* Add `queue:clear` task to clear a queue.

## v11.2.1

### Bug fixes

* `Queue\DatabaseQueue` now uses `SELECT FOR UPDATE SKIP LOCKED` to lock the row atomically, and skip already locked rows.
* Add index to `jobs` table to speed up job retrieval and reduce lock contention.

## v11.2

### Breaking changes

* `Entity\QueueJob` entity now uses `int` for `createdAt` field instead of `string` (datetime).
* `Mail\QueuedMailService` now requires a `queueService` parameter instead of a `queue` parameter and allows specifying a queue name.

### New features

* `Queue\DatabaseQueue` now supports a `deleteJobsOnCompletion` parameter to control whether jobs are deleted after completion.

## v11.1.1

### Bug fixes

* `TaskRunner` long options now accept the `--option=value` form as well as `--option value`.

## v11.1

### New features

* `TaskRunnerTask` now supports retry logic with `--attempts` and `--backoff` options for handling task failures.

## v11.0

### Breaking changes

* `Database\Database` interface now require `rollbackTransaction()` method.
* `Queue\Queue` interface now require `markJobCompleted()` and `markJobFailed()` methods.
* `Queue\Queue` interface now require `dispatch()` to accept a `maxAttempts` parameter.
* `Queue\JobHandler` interface now requires `handle()` to return void instead of bool. Job handlers must throw exceptions on failure instead of returning false.
* Authentication system changed to use codes instead of links. Each of the four actions (`Login`, `Register`, `ChangeEmail`, `ResetPassword`) that require (e-mail) verification, now flow through `Verify` and require a code then continue to the final step (`LoginVerify`, `RegisterVerify`, `ChangeEmailVerify`, `ResetPasswordVerify`). Requires routes for the final steps, new e-mail templates for `email-verification-code`, `change-email-verification-code`, possibly configuring action location in your config.
* `ValidationRule` interface lost `getErrorExtraMessage()` method. Use translation strings (with automatic extra message substitution) instead.
* `Validator` interface now requires `getRequiredErrorMessage()` and `getRequiredErrorParams()` methods to support customization of the error message for required fields.
* Removed `afterAuthentication()` method from `Login` action, listen to `UserLoggedIn` event instead.
* Authentication actions are now extensible through extension interfaces, instead of overriding the actions themselves. Migration requires implementing the new extension interface instead of extending the action class. Function names are similar, but not identical. Behavior is identical.
* Remove forced CAPTCHA protection from `Login` and `Register` actions. Can now be implemented with the extension interfaces.

### New features

* Updated `config:show` to allow showing a specific configuration node.
* New console command `definitions:show` to show the currently loaded definition files and their contents.
* Queue\DatabaseQueue now reserves jobs for processing and releases them back to the queue if they fail.
* Queue\DatabaseQueue now reschedules failed jobs with exponential backoff.
* Introduce Mail\MailBackend interface for asynchronous email sending.
* Introduce default job handlers for RawMailJob and TemplateMailJob.
* Introduce default job handler for EventJob.
* Introduce exception classes for job handling errors: InvalidJobException, JobDataException, and JobExecutionException.

# v10 release

## v10.2.1

### Bug fixes

* Make sure logging channels reach ChannelManager

## v10.2

### New features

* `RepositoryCore::instantiateEntityFromData()` now supports a `prefix` parameter to handle cases where the identifier field is prefixed with a table alias.
* `RepositoryCore::getAliasedFields()` to support aliasing fields in queries.

## v10.1

### New features

* Database migrations now support custom index names.

### Bug fixes

## v10.0.1

### Bug fixes

* Database tasks handle NullDatabase instances gracefully.

## v10.0

### Breaking changes

* Console tasks must now return `TaskArgument` and `TaskOption` objects from `getArguments()` and `getOptions()`. Update custom tasks to instantiate these helpers and validate option values by throwing `ArgumentParserException` when needed.
* The `database_enabled` configuration option has been removed. If you need to disable the database, use a `NullDatabase` implementation.
* `versions.supported_framework` has been removed, due to semantic versioning.
* Non-kernel classes have moved out of `Core` namespace into their respective namespaces.
* `SanityCheck\Base` has been moved to `SanityCheck\SanityCheckBase`.
* `web_framework_definitions.php` has been renamed to `definitions.php` for consistency with plugin definition files.

### New features

* Introduced `Logging\ChannelManager` and `Logging\LogService` for configurable log channels. `LoggerInterface` resolves to the default channel, and you can redirect channels per environment through the new `logging.channels` configuration.
* Added `Core\HttpApplication` to encapsulate Slim bootstrapping and channel-aware exception reporting for the HTTP entry point.
* Added `Core\ConsoleApplication` to own CLI dispatch, improving help output and error handling in the `framework` script.
* Added the `config:show` console command for inspecting the merged configuration at runtime.

### Bug fixes

* Database migrations now exit with a non-zero status when a query fails, ensuring CI pipelines surface the failure.

# v9 release

## v9.11

### New features

* Optional definition files are supported by prefixing entries with `?` in `definition_files`; missing files are skipped instead of raising an error.
* Added the `cache:clear` console command, which flushes the configured cache backend.

### Bug fixes

* MigrationManager gracefully handles `scandir()` failures while scanning migration directories.

## v9.10.1

### Bug fixes

* `ConfigBuilder` now replaces numeric lists (for example `definition_files`, middleware stacks, routes, and translation directories) instead of merging them element by element.

## v9.10

### New features

* `TaskRunner` honours the `APP_ENV` environment variable to load `.env.<environment>` files before falling back to the default `.env`.

## v9.9.1

### Bug fixes

* DatabaseConversionManager now tolerates missing or null database connections when evaluating conversion state.

## v9.9

### New features

* Added `EntityCollection::getEntities()` to expose the underlying entity array when you need direct access.

## v9.8

### Behaviour changes

* `CsrfValidationMiddleware` no longer interacts with the blacklist service; failed validations only affect the current request.

## v9.7

### New features

* Repository filters gained support for `BETWEEN`, `NOT BETWEEN`, `IN`, and `NOT IN` operators, enabling richer query constraints.

## v9.6.1

### Bug fixes

* `SanityCheckTask` suppresses `SanityCheckException` so manual runs do not fail the CLI when a check reports a problem.

## v9.6

### New features

* The `task:run` console command gained options for continuous execution (`--continuous`, `--delay`, and `--max-runtime`).
* The `TaskRunner` instance is now available in the container, simplifying tasks that need to dispatch other tasks.

### Bug fixes

* Resolved several edge cases in `SanityCheckTask` when force-running checks.

## v9.5

### Internal changes

* Core container definitions were refactored to prefer autowiring. Review overrides of `Core\Recaptcha`, `Core\UserMailer`, and the translation loader to align with the new helper services (`templateOverrides`, `translationDirectories`).
* `TaskRunner` now exposes an `is_plaintext` container value so services can adjust output when running under the CLI.

## v9.4

### New features

* Support for app console tasks in framework app

## v9.3

### Breaking changes

* Authentication events now contain request for audit purposes
* Interfaces for internal user authentication services changes 

## v9.2

* Improved conversion support to new migration system

## v9.1

### New features

* Central bootstrapping for Tasks

## v9.0

### New features

* Ability to configure Validation Rule error message
* Support for indexes in DatabaseManager
* Implementation of DatabaseQueue
* Support for .env and environment variable for configuration

### Breaking changes

* Database migration changes for scheme files to migration files
* Introduction of ErrorReport that is passed around instead of error array
* Move multiple Core classes to other WebFramework namespaces
* Move Rule classes to Validation\Rule
* Move Validator classes to Validation\Validator
* Renamed EntityInterface to Entity
* Renamed TaskInterface to Task
* StoredValues replaced with StoredValuesService with new interface
* StoredUserValues and StoredUserValuesFactory replaced with StoredUserValuesService with new interface

### Internal changes

* Renamed getCurrentVersion() to getCurrentAppVersion() in DatabaseManager

# v8 release

## v8.5

### Light changes

* Changed CSRF validation middleware to no longer require 'do' parameter
  and only check for state-changing methods

## v8.4

### Light changes

* Added PHPDoc
* Added documentation
* Better error handling in case of non-defined error handlers

## v8.3

### Light changes

* Expanded color configuration options of input helpers

## v8.2

### Light changes

* Cache authenticated user in DatabaseAuthenticationService

## v8.1

### Light changes

* Introduced MinValue and MaxValue rules
* postmark-php updated to v6
* MailReportFunction sends less on repeated errors
* Introduced RuntimeEnvironment to encapsulate runtime values

## v8.0

### Breaking changes
* BREAK: PHP based templates removed
* BREAK: ActionCore, ApiAction and PageAction removed
* BREAK: CamelCaseAutoLoader removed
* BREAK: DataCore and FactoryCore removed
* BREAK: AssertService removed
* BREAK: Caching removed from RepositoryCore
* BREAK: EntityCore / EntityInterface changed

###
# v7 release

## v7.11

### Light changes

* Database query functions throw on error
* check() added to InputValidationService
* Convert scripts and index.php to Task
* DatabaseManager supports Task as action
* Add init() to Register, Login, ChangePassword and ChangeEmail

## v7.10

### Light changes

* Introduced RouteSet class

## v7.9

### Light changes

* Removed deprecated event handling from StripeFactory

## v7.8

### Light changes

* Split middlewares into pre and post routing

## v7.7

### New Features

* Add getFromQuery() to RepositoryCore

## v7.6

### Light changes

* ValidationException constructor changed for simplicity, MultiValidationException added
* Support additional Database-generated ID fields (like guid) in EntityCore / RepositoryCore

## v7.4

### Light changes

* BuildInfoService->getInfo() replaces DebugService->getBuildInfo()

### New Features

* Support Instrumentation

## v7.3

### Light changes

* CustomIdValidator now named IdValidator
* Validators not required by default (except PasswordValidator)

### New Features

* CustomNumberValidator added
* Support function arguments in condensed trace report
* Support toArray() on uninitialized EntityCore fields

## v7.2

### Light changes

* CustomValidator interface changed to fluid

### New Features

* Support for minLength() in CustomValidator

## v7.1

### New Features
* Random Generation not behind RandomProvider interface
* Add UploadValidationService

## v7.0

### Breaking changes
* BREAK: SanityCheckInterface renamed to SanityCheckModule
* BREAK: requirePresence for translations now defaults to false
* BREAK: Account related service are broken up and now in Security namespace
* BREAK: Promote composition instead of inheritance (protected to private)

### New Features
* Saving new Entity objects with RepositoryCore now supported
* Add InputValidationService
* Add TranslationService
* MessageService automatically translates recognized tags
* Add UrlBuilder with support for messages
* Support redirect url building directly from ResponseEmitter
* Migrated example templates to latte
* Support fromArray() in EntityCore to mass-fill

# v6 release

## v6.10

* Add getRights() to UserRightsService
* AuthenticationService now has getAuthenticatedUserId()
* ValidationException to indicate validation of input variables has failed

## v6.9

New features:

* Ability to set template in Login, Register, ChangePassword and ChangeEmail
* Call external functions on items in the EntityCollection with call()
* RedirectException to break out of code flow and trigger a redirect

## v6.8

Breaking changes:

* AuthenticationService->userHasPermissions() has been removed, use UserRightService instead
* BrowserSessionService has been removed, moved to use odan/session package

## v6 major overhaul

Major internal changes to move to move to Slim Framework v4 as core handler.
WebFramework adds supporting code to quickly prototype and make applications.

Old code can still run on top of the major overhaul with minimal changes.

* Replace calls to WF::verify() with exception throwing
* Replace calls to WF::get_config() with config_service->get or container->get
* Remove calls to framework
* Use init() instead of constructor modifications (for ActionCore / FactoryCore / DataCore dependents)
* Add Database as first parameter to StoredValues constructor
* Replace calls to SenderCore::send_raw with MessageHandler->send_raw_mail()
* Replace calls to SenderCore::send with MessageHandler->send_template_mail()
* Rewrite register_route() and register_redirect() to Slim route functions (Use RouteConvertor example in scripts)
* Change prototype to (Request, Response, array) and call $this->handle_permissions_and_inputs() to all ApiAction derived functions
* Get app_dir, server_name and host_name via constructor or container
* Change all calls to camelCase instead of snake_case (Use CaseConvertor exampls in scripts)

Configuration changes:
* 'debug_mail' has been removed
* 'registration' has been removed
* 'site_name' has been removed
* 'cache_enabled' has been removed. Override container definitions instead
* 'auth_mode' and 'auth_module' have been removed. Override container definitions instead
* 'auth_dir' has changed to '/config/auth' instead of /includes/auth'
* 'actions.app_namespace' has been removed

Over time you should:
* Move to Dependency Injection for all your classes
* Move your actions to Slim prototype
* Move away from using 'new'

As a result of the fundamental changes there are some internal and external changes. Internal breaking changes only affect you if you used internal classes directly. External breaking changes affect the public interface.

External:
* Moved to camelCase from snake_case
* BaseFactory constructor has changed
* CacheInterface is now called Cache
* Database constructor has changed
* Image, constructor has changes and does not automatically analyze when constructed
* Recaptcha constructor has changed
* RedisCache constructor has changed
* SenderCore has been removed. Use MailService instead
* Config option: actions.default_action has been removed. All routes are explicit
* Config option: authenticator.user_class introduced for class of user objects to retrieve
* Config option: document_root has been removed
* Config option: sanity_check_module has been removed. Use sanity_check_modules instead
* Config option: sender_core.handler_class has been removed
* StripeFactory constructor has changed
* StoredValues constructor has changed
* UserStoredValues constructor has changed
* Webhook interface has changed
* WF->add_message() has been removed. Use MessageService instead
* WF->auth_array has been removed. Use AuthenticationService->get_authenticated_user() instead
* WF->authenticate() has been removed. Use AuthenticationService instead
* WF->deauthenticate() has been removed. Use AuthenticationService instead
* WF->get_authenticated() has been removed. Use AuthenticationService->get_authenticated_user() instead
* WF->get_build_info() has been removed. Use DebugService instead
* WF->get_input() has been removed. Use the Request with ValidatorService instead
* WF->get_messages() has been removed. Use MessageService instead
* WF->get_raw_input() has been removed. Use the Request instead
* WF->invalidate_sessions() has been removed. Use AuthenticationService instead
* WF->is_authenticated() has been removed. Use AuthenticationService instead
* WF->user_has_permissions() has been removed. Use AuthenticationService instead
* WF->validate_input() has been removed. Use ValidatorService instead
* WFWebHandler has been removed
* WFWebHandler->register_redirect() has been removed. Use Slim routing instead
* WFWebHandler->register_route() has been removed. Use Slim routing instead
* WF->set_configs() has been removed. Should pass all configs to ConfigBuilder directly (Including the base config (by default at /vendor/avoutic/web-framework/includes/BaseConfig.php))
* WF->throw_exception_on_error() has been removed. Exception always thrown from AssertService

Internal:
* AuthRedirect has been refactored to Security\DatabaseAuthenticationService
* AuthWwwAuthenticate has been removed
* Authenticator has been refactored to Security\AuthenticationService
* WF has been removed
* WFWebHandler has been removed
* FrameworkCore has been removed
* WFSecurity has been split into CsrfService, ConfigService and ProtectService

# v5 release

Required changes for v5:
* Run on PHP 8.2

# v4 release

Required changes for v4:
* Update code to PHPv8.1 type and visibility hints (See below)
* Database->Connect() deprecated. Use Database->connect() instead
* Database->GetLastError() deprecated. Use Database->get_last_error() instead
* Database->InsertQuery() deprecated. Use Database->insert_query() instead
* Database->Query() deprecated. User Database->query() instead
* FrameworkCore-silent_verify() deprecated. Use report_error() instead
* FrameworkCore->urldecode_and_verify_array() deprecated. Use decode_and_verify_array() instead
* FrameworkCore->urlencode_and_auth_array() deprecated. Use encode_and_auth_array() instead
* FrameworkCore->verify() does not support $silent anymore, use FrameworkCore->report_error() instead
* PageCore->get_input_var() only returns filtered string values now.
* PageCore->get_input_array() should be used to retrieve filtered array input.
* PageCore->get_raw_input_var() only returns raw string values now.
* PageCore->get_raw_input_array() should be used to retrieve raw array input.
* PageBasic->load_file() deprecated.
* PageBasic->get_config_store() deprecated. Use PageBasic->get_stored_values() instead.
* PageBasic->get_config_values() deprecated. Use stored values directly.
* PageBasic->get_config_value() deprecated. Use stored values directly.
* PageBasic->set_config_values() deprecated. Use stored values directly.
* PageBasic->delete_config_value() deprecated. Use stored values directly.
* WF::$views deprecated.
* WF::$site_views deprecated.
* WF::$site_templates deprecated.
* WF::$site_frames deprecated.
* WF::$site_includes deprecated.
* WF does not load WF::$site_includes/site_defines.inc.php anymore. Use preloader instead.
* Use the WebFramework namespace to address classes
* Rename Page related items to Action (See below)
* DBManager renamed to DatabaseManager

Files / classes fully removed in v4:
* htdocs/sha1.js removed from framework
* PageContactBase and template removed from framework
* iPageModule, UrlConfig and Translator removed from framework
* LocaleFactory and CountryLocaleFactory removed from framework
* ShortenerCore and GoogleShortener removed from framework
* TranslationFactory removed from framework
* PageDownload removed from framework
* PageGrabIdentity removed from framework
* PageManageUser and template removed from framework
* PageUserOverview and template removed from framework

## Type hints migration

Unfortunately most code in includes is unique to your project, and you will have to add type hints yourself.

But in views, the functions that you need to alter are mostly from PageBasic, so you can automate part by running the following commands. This assumes you did already move them from *views* to *actions*:

```
sed --follow-symlinks -i -e 's/^    static protected \$table_name/    static protected string \$table_name/' includes/*.php
sed --follow-symlinks -i -e 's/^    static protected \$base_fields/\n    \/**\n     * @var array<string>\n     *\/\n    static protected array \$base_fields/' includes/*.php
sed --follow-symlinks -i -e 's/^    function get_info()$/    \/**\n     * @return array<mixed>\n     *\/\n    function get_info(): array/' includes/*.php
sed --follow-symlinks -i -e 's/^    function display_content()$/    protected function display_content(): void/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function display_footer()$/    protected function display_footer(): void/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function display_header()$/    protected function display_header(): void/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function do_logic()$/    protected function do_logic(): void/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function get_frame_file()$/    protected function get_frame_file(): string/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function get_title()$/    protected function get_title(): string/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    static function get_permissions()$/    \/**\n     * @return array<string>\n     *\/\n    static function get_permissions(): array/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    static function get_filter()$/    \/**\n     * @return array<string, string>\n     *\/\n    static function get_filter(): array/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    static function custom_get_filter()$/    \/**\n     * @return array<string, string>\n     *\/\n    static function custom_get_filter(): array/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function custom_prepare_page_content()$/    function custom_prepare_page_content(): void/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function custom_value_check()$/    function custom_value_check(): bool/' actions/**/*.php
sed --follow-symlinks -i -e 's/^    function/    public function/' includes/*.php actions/**/*.php
```

## Page to Action migration

Migrating from Page to Action should be straightforward replacements that you can mostly automate with the code below:
```
mv views actions
sed --follow-symlinks -i -e 's/PageCore/ActionCore/g' includes/*.php actions/**/*.php templates/*.php
sed --follow-symlinks -i -e 's/PageService/ApiAction/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e 's/PageBasic/PageAction/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e 's/PageBasic/PageAction/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e 's/page\.base_url/base_url/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e 's/page\.default_frame_file/actions.default_frame_file/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e 's/page\.default_page/actions.default_action/g' includes/*.php actions/**/*.php
sed --follow-symlinks -i -e "s/get_config('pages\./get_config('actions./g" includes/*.php actions/**/*.php
```

You might have to adjust your config and own preload instructions for the autoloader as well.

## Camelcase to PSR4 migration

WebFramework has the CamelCaseAutoLoader in case your files have lowercase/underscore names and classes have CamelCase.

If you want to rename your files all at once in a directory you can use the following to extract the class/trait name and rename based on that:

```
for i in `find . -type f`; do dir="$( echo $i | rev | cut -f 2- -d '/' | rev)"; j="$(grep "^class\|trait" $i | cut -f 2 -d ' ')"; echo $i "->" $dir "/" $j.php; git mv "$i" "$dir/$j.php"; done
```

## Config file migrations
#
For the config file:

* 'versions.supported_framework' should be set to 4
* 'pages.' was renamed to 'actions.'
* 'page.base_url' is now top level 'base_url'
* 'page.default_frame_file' is at 'actions.default_frame_file'
* 'page.default_page' is at 'actions.default_action' and should contain a ActionCore derived object (within actions.app_namespace) and function name
* 'actions.app_namespace' is introduced if you use another namespace (default is 'App\Actions')
* 'error_handlers.' an ActionCore derived abject name (within actions.app_namespace) is needed
* 'sender_core.handler_class' now expects a fully namespaced ActionCore derived abject name is needed

# v3 release

Actions to take to migrate from v1/v2 to v3 of web-framework

* Remove RewriteCond page and don't add page parameter to index.php
* Remove SITE_NAME and MAIL_ADDRESS from site_defines.inc.php
* There are no return values for update_field(), update(), increase_field(), decrease_field()
* $global_info is not needed in constructors and static DataCore functions anymore
* $this->database->Query() -> $this->query()
* Migrate core_object functions to static DataCore equivalents
* send_404() -> $this->exit_send_404()
* verify() -> $this->verify() or WF::verify()
* No more $global_config, use $this->get_config() or WF::get_config()
* WF:: before $includes, $site_includes and $views
* add_message_to_url() -> $this->get_message_for_url()
* add_blacklist_entry() -> $this->add_blacklist_entry()
* encode_and_auth_array() -> $this->encode_and_auth_array()
* decode_and_verify_array() -> $this->decode_and_verify_array()
* $this->check_required() -> $this->get_input_var('xyz', true)
* $this->state['messages'] => $this->get_messages()
* $this->state['user_id'] => $this->get_authenticated('user_id')
* $this->state['username'] => $this->get_authenticated('username')
* $this->state['user'] => $this->get_authenticated()['user']
* $this->state['input'] => $this->get_input_var()
* get_crsf_token() to $this->get_csrf_token()
* Routes from register_routes() to individual $framework->register_route()

## Scripts

```
sed --follow-symlinks -i -e "s/\$this->check_required(\(\\\$\w\+\), '\(\w\+\)'/\1 = \$this->get_input_var('\2', true/g" actions/**/*.php
sed --follow-symlinks -i -e "s/add_message_to_url/\$this->get_message_for_url/g" actions/**/*.php
sed --follow-symlinks -i -e "s/ verify(/ \$this->verify(/g" includes/*.php actions/**/*.php templates/**/*.php
sed --follow-symlinks -i -e "s/ send_404/ \$this->exit_send_404/g" includes/*.php actions/**/*.php
sed --follow-symlinks -i -e "s/ \$this->database->Query/ \$this->query/g" includes/*.php
sed --follow-symlinks -i -e "s/ get_csrf_token()/ \$this->get_csrf_token()/g" templates/**/*.php
sed --follow-symlinks -i -e "s/<?=get_csrf_token()/<?=\$this->get_csrf_token()/g" templates/**/*.php
```
