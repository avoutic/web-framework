# v7 release

## v7.11

### Light changes

* Database query functions throw on error
* check() added to InputValidationService
* Convert scripts and index.php to TaskInterface

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
