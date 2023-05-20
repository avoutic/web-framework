<?php

namespace WebFramework\Core;

use Cache\Adapter\Redis\RedisCachePool;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class WF
{
    private string $app_dir = '';

    private static WF $framework;
    private static Database $main_db;       // Only for DataCore and StoredValues abstraction
    private static Cache $static_cache;     // Only for DataCore and StoredValues abstraction

    protected bool $initialized = false;
    private Database $main_database;

    /**
     * @var array<Database>
     */
    private array $aux_databases = [];
    private Cache $cache;

    // Services
    //
    protected ?AssertService $assert_service = null;
    protected ?BaseFactory $base_factory = null;
    protected ?Security\AuthenticationService $authentication_service = null;
    protected ?Security\BlacklistService $blacklist_service = null;
    protected ?BrowserSessionService $browser_session_service = null;
    protected ?ConfigService $config_service = null;
    protected ?Security\CsrfService $csrf_service = null;
    protected ?DatabaseManager $database_manager = null;
    protected ?DebugService $debug_service = null;
    protected ?LatteRenderService $latte_render_service = null;
    protected ?MailService $mail_service = null;
    protected ?ObjectFunctionCaller $object_function_caller = null;
    protected ?PostmarkClientFactory $postmark_client_factory = null;
    protected ?ReportFunction $report_function = null;
    protected ?ResponseEmitter $response_emitter = null;
    protected ?ResponseFactory $response_factory = null;
    protected ?RouteService $route_service = null;
    protected ?Security\ConfigService $secure_config_service = null;
    protected ?Security\ProtectService $protect_service = null;
    protected ?UserMailer $user_mailer = null;
    protected ?ValidatorService $validator_service = null;
    protected ?WFWebHandler $web_handler = null;

    /** @var array<string> */
    private array $container_stack = [];

    /**
     * @var array<string>
     */
    private array $configs = [
        '/vendor/avoutic/web-framework/includes/BaseConfig.php',
        '/includes/config.php',
        '?/includes/config_local.php',
    ];

    private bool $check_db = true;
    private bool $check_app_db_version = true;
    private bool $check_wf_db_version = true;

    public function __construct()
    {
        // Immediately initialize cache with NullCache so that a cache is always available
        //
        $this->cache = new NullCache();
        self::$static_cache = $this->cache;

        // Determine app dir
        //
        $reflection = new \ReflectionClass(\Composer\Autoload\ClassLoader::class);
        $this->app_dir = dirname($reflection->getFileName(), 3);
    }

    public function get_assert_service(): AssertService
    {
        if ($this->assert_service === null)
        {
            $this->container_stack[] = 'assert_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->assert_service = new AssertService(
                $this->get_debug_service(),
                $this->get_report_function(),
            );

            array_pop($this->container_stack);
        }

        return $this->assert_service;
    }

    public function get_authentication_service(): Security\AuthenticationService
    {
        if ($this->authentication_service === null)
        {
            $this->container_stack[] = 'authentication_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->authentication_service = new Security\DatabaseAuthenticationService(
                $this->get_cache(),
                $this->get_main_db(),
                $this->get_browser_session_service(),
                $this->get_base_factory(),
                $this->get_config('authenticator.session_timeout'),
                $this->get_config('authenticator.user_class'),
            );

            array_pop($this->container_stack);
        }

        return $this->authentication_service;
    }

    public function get_base_factory(): BaseFactory
    {
        if ($this->base_factory === null)
        {
            $this->container_stack[] = 'base_factory';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->base_factory = new BaseFactory(
                $this->get_main_db(),
                $this->get_assert_service(),
            );

            array_pop($this->container_stack);
        }

        return $this->base_factory;
    }

    public function get_blacklist_service(): Security\BlacklistService
    {
        if ($this->blacklist_service === null)
        {
            $this->container_stack[] = 'blacklist_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            if ($this->get_config_service()->get('security.blacklist.enabled') == true)
            {
                $this->blacklist_service = new Security\NullBlacklistService();
            }
            else
            {
                $this->blacklist_service = new Security\DatabaseBlacklistService(
                    $this->get_main_db(),
                    $this->get_config('security.blacklist'),
                );
            }

            array_pop($this->container_stack);
        }

        return $this->blacklist_service;
    }

    public function get_browser_session_service(): BrowserSessionService
    {
        if ($this->browser_session_service === null)
        {
            $this->container_stack[] = 'browser_session_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $browser_session_service = new BrowserSessionService();
            $browser_session_service->start(
                $this->get_config('host_name'),
                $this->get_config('http_mode'),
            );

            $this->browser_session_service = $browser_session_service;

            array_pop($this->container_stack);
        }

        return $this->browser_session_service;
    }

    public function get_config_service(): ConfigService
    {
        if ($this->config_service === null)
        {
            $this->container_stack[] = 'config_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $config_builder = new ConfigBuilder(
                $this->get_app_dir(),
            );
            $config_builder->build_config(
                $this->configs,
            );
            $config_builder->populate_internals($_SERVER['SERVER_NAME'] ?? '', $_SERVER['SERVER_NAME'] ?? '');

            $config_service = new ConfigService($config_builder->get_config());

            $this->config_service = $config_service;

            array_pop($this->container_stack);
        }

        return $this->config_service;
    }

    public function get_csrf_service(): Security\CsrfService
    {
        if ($this->csrf_service === null)
        {
            $this->container_stack[] = 'csrf_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->csrf_service = new Security\CsrfService(
                $this->get_browser_session_service(),
            );

            array_pop($this->container_stack);
        }

        return $this->csrf_service;
    }

    public function get_database_manager(): DatabaseManager
    {
        if ($this->database_manager === null)
        {
            $this->container_stack[] = 'database_manager';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->database_manager = new DatabaseManager(
                $this->get_assert_service(),
                $this->get_main_db(),
                new StoredValues($this->get_main_db(), 'db'),
            );

            array_pop($this->container_stack);
        }

        return $this->database_manager;
    }

    public function get_debug_service(): DebugService
    {
        if ($this->debug_service === null)
        {
            $this->container_stack[] = 'debug_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->debug_service = new DebugService(
                $this,
                $this->get_app_dir(),
                $this->get_config('server_name'),
            );

            array_pop($this->container_stack);
        }

        return $this->debug_service;
    }

    public function get_report_function(): ReportFunction
    {
        if ($this->report_function === null)
        {
            $this->container_stack[] = 'report_function';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->report_function = new MailReportFunction(
                $this->get_cache(),
                $this->get_mail_service(),
                $this->get_config('sender_core.assert_recipient'),
            );

            array_pop($this->container_stack);
        }

        return $this->report_function;
    }

    public function get_object_function_caller(): ObjectFunctionCaller
    {
        if ($this->object_function_caller === null)
        {
            $this->container_stack[] = 'object_function_caller';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->object_function_caller = new ObjectFunctionCaller(
                $this->get_assert_service(),
                $this->get_authentication_service(),
                $this->get_validator_service(),
            );

            array_pop($this->container_stack);
        }

        return $this->object_function_caller;
    }

    public function get_response_emitter(): ResponseEmitter
    {
        if ($this->response_emitter === null)
        {
            $this->container_stack[] = 'response_emitter';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->response_emitter = new ResponseEmitter(
                $this->get_config_service(),
                $this->get_object_function_caller(),
                $this->get_response_factory(),
            );

            array_pop($this->container_stack);
        }

        return $this->response_emitter;
    }

    public function get_response_factory(): ResponseFactory
    {
        if ($this->response_factory === null)
        {
            $this->container_stack[] = 'response_factory';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->response_factory = new ResponseFactory();

            array_pop($this->container_stack);
        }

        return $this->response_factory;
    }

    public function get_route_service(): RouteService
    {
        if ($this->route_service === null)
        {
            $this->container_stack[] = 'route_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->route_service = new RouteService(
                $this->get_config('base_url'),
            );

            array_pop($this->container_stack);
        }

        return $this->route_service;
    }

    public function get_secure_config_service(): Security\ConfigService
    {
        if ($this->secure_config_service === null)
        {
            $this->container_stack[] = 'secure_config_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->secure_config_service = new Security\ConfigService(
                $this->get_app_dir().$this->get_config('security.auth_dir'),
            );

            array_pop($this->container_stack);
        }

        return $this->secure_config_service;
    }

    public function get_protect_service(): Security\ProtectService
    {
        if ($this->protect_service === null)
        {
            $this->container_stack[] = 'protect_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->protect_service = new Security\ProtectService(
                $this->get_config('security'),
            );

            array_pop($this->container_stack);
        }

        return $this->protect_service;
    }

    public function get_user_mailer(): UserMailer
    {
        if ($this->user_mailer === null)
        {
            $this->container_stack[] = 'user_mailer';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->user_mailer = new UserMailer(
                $this->get_mail_service(),
                $this->get_config('sender_core.default_sender'),
            );

            array_pop($this->container_stack);
        }

        return $this->user_mailer;
    }

    public function get_validator_service(): ValidatorService
    {
        if ($this->validator_service === null)
        {
            $this->container_stack[] = 'validator_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->validator_service = new ValidatorService(
                $this->get_assert_service(),
            );

            array_pop($this->container_stack);
        }

        return $this->validator_service;
    }

    public function get_web_handler(): WFWebHandler
    {
        if ($this->web_handler === null)
        {
            $this->container_stack[] = 'web_handler';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->web_handler = new WFWebHandler(
                $this->get_authentication_service(),
                $this->get_blacklist_service(),
                $this->get_config_service(),
                $this->get_csrf_service(),
                $this->get_object_function_caller(),
                $this->get_protect_service(),
                $this->get_response_emitter(),
                $this->get_response_factory(),
                $this->get_route_service(),
                $this->get_validator_service(),
            );

            array_pop($this->container_stack);
        }

        return $this->web_handler;
    }

    public function get_latte_render_service(): LatteRenderService
    {
        if ($this->latte_render_service === null)
        {
            $this->container_stack[] = 'latte_render_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $this->latte_render_service = new LatteRenderService(
                $this->get_assert_service(),
                new \Latte\Engine(),
                $this->get_app_dir().'/templates',
                '/tmp/latte',
            );

            array_pop($this->container_stack);
        }

        return $this->latte_render_service;
    }

    public function get_mail_service(): MailService
    {
        if ($this->mail_service === null)
        {
            $this->container_stack[] = 'mail_service';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $api_key = $this->get_secure_config_service()->get_auth_config('postmark');

            $this->mail_service = new PostmarkMailService(
                new PostmarkClientFactory($api_key),
                $this->get_config('sender_core.default_sender'),
                $this->get_config('server_name'),
            );

            array_pop($this->container_stack);
        }

        return $this->mail_service;
    }

    public function get_postmark_client_factory(): PostmarkClientFactory
    {
        if ($this->postmark_client_factory === null)
        {
            $this->container_stack[] = 'postmark_client_factory';
            if (count($this->container_stack) > 25)
            {
                print_r($this->container_stack);

                exit();
            }

            $api_key = $this->get_secure_config_service()->get_auth_config('postmark');

            $this->postmark_client_factory = new PostmarkClientFactory($api_key);

            array_pop($this->container_stack);
        }

        return $this->postmark_client_factory;
    }

    public static function assert_handler(string $file, int $line, string $message, string $error_type): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $framework = self::get_framework();
        $framework->internal_assert_handler($message, $error_type);
    }

    public function internal_assert_handler(string $message, string $error_type): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $assert_service = $this->get_assert_service();
        $assert_service->report_error(message: $message, error_type: $error_type);
    }

    public static function verify(bool|int $bool, string $message): void
    {
        $framework = self::get_framework();
        $framework->internal_verify($bool, $message);
    }

    public function internal_verify(bool|int $bool, string $message): void
    {
        if ($bool)
        {
            return;
        }

        $assert_service = $this->get_assert_service();
        $assert_service->verify($bool, $message);
    }

    // Send a triggered error message but continue running
    //
    /**
     * @param array<mixed> $stack
     */
    public static function report_error(string $message, array $stack = []): void
    {
        $framework = self::get_framework();
        $framework->internal_report_error($message, $stack);
    }

    /**
     * @param array<mixed> $stack
     */
    public function internal_report_error(string $message, array $stack = []): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        $assert_service = $this->get_assert_service();
        $assert_service->report_error($message, $stack, $request);
    }

    public static function blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        $framework = self::get_framework();
        $framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    public function internal_blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        if ($bool)
        {
            return;
        }

        $this->add_blacklist_entry($reason, $severity);

        exit();
    }

    public function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        $ip = (isset($_SERVER['REMOTE_ADDR'])) ? $_SERVER['REMOTE_ADDR'] : 'app';

        $user_id = null;
        if ($this->is_authenticated())
        {
            $user = $this->get_authentication_service()->get_authenticated_user();
            $user_id = $user->id;
        }

        $this->get_blacklist_service()->add_entry($ip, $user_id, $reason, $severity);
    }

    public static function shutdown_handler(): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $framework = self::get_framework();
        $framework->internal_shutdown_handler();
    }

    public function internal_shutdown_handler(): void
    {
        $last_error = error_get_last();
        if (!$last_error)
        {
            return;
        }

        $message = "{$last_error['file']}:{$last_error['line']}:{$last_error['message']}";

        $assert_service = $this->get_assert_service();
        $assert_service->report_error(message: $message, error_type: (string) $last_error['type']);

        // Don't trigger other handlers after this call
        //
        exit();
    }

    protected function exit_error(string $short_message, string $message): void
    {
        echo('Fatal error: '.$short_message.PHP_EOL);
        echo($message.PHP_EOL);

        exit();
    }

    public static function get_framework(): self
    {
        return self::$framework;
    }

    public static function get_app_dir(): string
    {
        $framework = self::get_framework();

        return $framework->internal_get_app_dir();
    }

    public function internal_get_app_dir(): string
    {
        return $this->app_dir;
    }

    public static function get_config(string $location = ''): mixed
    {
        $framework = self::get_framework();

        return $framework->get_config_service()->get($location);
    }

    public function internal_get_config(string $location = ''): mixed
    {
        @trigger_error('Deprecated. Directly call ConfigService instead', E_USER_DEPRECATED);

        return $this->get_config_service()->get($location);
    }

    public function get_db(string $tag = ''): Database
    {
        if (!strlen($tag))
        {
            return $this->main_database;
        }

        $this->internal_verify(array_key_exists($tag, $this->aux_databases), 'Database not registered');

        return $this->aux_databases[$tag];
    }

    // Only relevant for DataCore and StoredValues to retrieve main database in static functions
    //
    public static function get_main_db(): Database
    {
        return self::$main_db;
    }

    public function get_cache(): Cache
    {
        return $this->cache;
    }

    // Only relevant for DataCore and StoredValues to retrieve main database in static functions
    //
    public static function get_static_cache(): Cache
    {
        return self::$static_cache;
    }

    public function skip_db_check(): void
    {
        $this->check_db = false;
    }

    public function skip_app_db_version_check(): void
    {
        $this->check_app_db_version = false;
    }

    public function skip_wf_db_version_check(): void
    {
        $this->check_wf_db_version = false;
    }

    /**
     * @param array<string> $configs Config files to merge on top of each other in order.
     *                               File locations should be relative to the app dir
     *                               including leading /. If it starts with a '?' the file
     *                               does not have to be present.
     */
    public function set_configs(array $configs): void
    {
        $this->configs = $configs;
    }

    public function init(): void
    {
        // Make sure static wrapper functions can work
        //
        self::$framework = $this;

        mt_srand();

        $this->check_file_requirements();

        // Enable debugging if requested
        //
        if ($this->get_config_service()->get('debug') == true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set('display_errors', '1');
        }
        else
        {
            register_shutdown_function([$this, 'internal_shutdown_handler']);
        }

        // Set default timezone
        //
        date_default_timezone_set($this->get_config_service()->get('timezone'));

        $this->check_config_requirements();
        $this->load_requirements();

        if ($this->get_config_service()->get('database_enabled') == true)
        {
            $this->init_databases();
        }

        $this->check_compatibility();

        $this->init_cache();

        $this->initialized = true;
    }

    private function check_file_requirements(): void
    {
        foreach ($this->configs as $config_file)
        {
            // Skip optional files
            if ($config_file[0] == '?')
            {
                continue;
            }

            if (!is_file("{$this->app_dir}{$config_file}"))
            {
                $this->exit_error(
                    'Missing base requirement',
                    "One of the required files ({$config_file}) is not found on the server."
                );
            }
        }
    }

    private function load_requirements(): void
    {
        // Check for special loads before anything else
        //
        if ($this->get_config_service()->get('preload') == true)
        {
            if (!file_exists("{$this->app_dir}/includes/preload.inc.php"))
            {
                $this->exit_error(
                    'Preload indicated but not present',
                    'The file "includes/preload.inc.php" does not exist.'
                );
            }

            require_once "{$this->app_dir}/includes/preload.inc.php";
        }

        // Load global and site specific defines
        //
        require_once __DIR__.'/defines.inc.php';
    }

    private function check_config_requirements(): void
    {
        // Check for required values
        //
        if (!strlen($this->get_config_service()->get('sender_core.default_sender')))
        {
            $this->exit_error(
                'No default sender specified',
                'One of the required config values (sender_core.default_sender) is missing. '.
                'Required for mailing verify information'
            );
        }

        if (!strlen($this->get_config_service()->get('sender_core.assert_recipient')))
        {
            $this->exit_error(
                'No assert recipient specified',
                'One of the required config values (sender_core.assert_recipient) is missing. '.
                'Required for mailing verify information'
            );
        }

        if (strlen($this->get_config_service()->get('security.hmac_key')) < 20)
        {
            $this->exit_error(
                'Required config value missing',
                'No or too short HMAC Key provided (Minimum 20 chars) in (security.hmac_key).'
            );
        }

        if (strlen($this->get_config_service()->get('security.crypt_key')) < 20)
        {
            $this->exit_error(
                'Required config value missing',
                'No or too short Crypt Key provided (Minimum 20 chars) in (security.crypt_key).'
            );
        }
    }

    private function init_databases(): void
    {
        // Start the database connection(s)
        //
        $main_db_tag = $this->get_config_service()->get('database_config');
        $main_config = $this->get_secure_config_service()->get_auth_config('db_config.'.$main_db_tag);

        $mysql = new \mysqli(
            $main_config['database_host'],
            $main_config['database_user'],
            $main_config['database_password'],
            $main_config['database_database']
        );

        if ($mysql->connect_error)
        {
            $this->exit_error(
                'Database server connection failed',
                'The connection to the database server failed.'
            );
        }

        $this->main_database = new MysqliDatabase($mysql);
        self::$main_db = $this->main_database;

        // Set the Database in the DebugService after init
        //
        $this->get_debug_service()->set_database($this->main_database);

        // Open auxilary database connections
        //
        foreach ($this->get_config_service()->get('databases') as $tag)
        {
            $tag_config = $this->get_secure_config_service()->get_auth_config('db_config.'.$tag);

            $mysql = new \mysqli(
                $tag_config['database_host'],
                $tag_config['database_user'],
                $tag_config['database_password'],
                $tag_config['database_database']
            );

            if ($mysql->connect_error)
            {
                $this->exit_error(
                    "Database server connection for '{$tag}' failed",
                    'The connection to the database server failed.'
                );
            }

            $this->aux_databases[$tag] = new MysqliDatabase($mysql);
        }
    }

    private function check_compatibility(): void
    {
        // Verify all versions for compatibility
        //
        $required_wf_version = FRAMEWORK_VERSION;
        $supported_wf_version = $this->get_config_service()->get('versions.supported_framework');

        if ($supported_wf_version == -1)
        {
            $this->exit_error(
                'No supported Framework version configured',
                'There is no supported framework version provided in "versions.supported_framework". '.
                "The current version is {$required_wf_version} of this Framework."
            );
        }

        if ($required_wf_version != $supported_wf_version)
        {
            $this->exit_error(
                'Framework version mismatch',
                'Please make sure that this app is upgraded to support version '.
                "{$required_wf_version} of this Framework."
            );
        }

        if ($this->get_config_service()->get('database_enabled') != true || !$this->check_db)
        {
            return;
        }

        $required_wf_db_version = FRAMEWORK_DB_VERSION;
        $required_app_db_version = $this->get_config_service()->get('versions.required_app_db');

        // Check if base table is present
        //
        if (!$this->main_database->table_exists('config_values'))
        {
            $this->exit_error(
                'Database missing config_values table',
                'Please make sure that the core Framework database scheme has been applied. (by running db_init script)'
            );
        }

        $stored_values = new StoredValues($this->get_main_db(), 'db');
        $current_wf_db_version = $stored_values->get_value('wf_db_version', '0');
        $current_app_db_version = $stored_values->get_value('app_db_version', '1');

        if ($this->check_wf_db_version && $required_wf_db_version != $current_wf_db_version)
        {
            $this->exit_error(
                'Framework Database version mismatch',
                'Please make sure that the latest Framework database changes for version '.
                "{$required_wf_db_version} of the scheme are applied."
            );
        }

        if ($this->check_app_db_version && $required_app_db_version > 0 && $current_app_db_version == 0)
        {
            $this->exit_error(
                'No app DB present',
                'Config (versions.required_app_db) indicates an App DB should be present. None found.'
            );
        }

        if ($this->check_app_db_version && $required_app_db_version > $current_app_db_version)
        {
            $this->exit_error(
                'Outdated version of the app DB',
                "Please make sure that the app DB scheme is at least {$required_app_db_version}. (Current: {$current_app_db_version})"
            );
        }
    }

    private function init_cache(): void
    {
        if ($this->get_config_service()->get('cache_enabled') == true)
        {
            // Start the Redis cache connection
            //
            $cache_config = $this->get_secure_config_service()->get_auth_config('redis');

            $redis_client = new \Redis();
            $result = $redis_client->pconnect(
                $cache_config['hostname'],
                $cache_config['port'],
                1,
                'wf',
                0,
                0,
                ['auth' => $cache_config['password']]
            );

            if ($result !== true)
            {
                $this->exit_error(
                    'Cache connection failed',
                    '',
                );
            }

            $cache_pool = new RedisCachePool($redis_client);

            try
            {
                // Workaround: Without trying to check something, the connection is not yet verified.
                //
                $cache_pool->hasItem('errors');
            }
            catch (\Throwable $e)
            {
                $this->exit_error(
                    'Cache connection failed',
                    '',
                );
            }
            $this->cache = new RedisCache($cache_pool);

            self::$static_cache = $this->cache;
        }
    }

    // Deprecated (Remove for v6)
    //
    public function get_sanity_check(): SanityCheckInterface
    {
        @trigger_error('WF->get_sanity_check()', E_USER_DEPRECATED);
        $class_name = $this->get_config_service()->get('sanity_check_module');

        return $this->instantiate_sanity_check($class_name);
    }

    /**
     * @param array<string, mixed> $config
     */
    public function instantiate_sanity_check(string $class_name, array $config = []): SanityCheckInterface
    {
        $this->verify(class_exists($class_name), "Sanity check module '{$class_name}' not found");

        $obj = new $class_name($config);
        $this->internal_verify($obj instanceof SanityCheckInterface, 'Sanity check module does not implement SanityCheckInterface');

        return $obj;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function get_sanity_checks_to_run(): array
    {
        $class_name = $this->get_config_service()->get('sanity_check_module');
        $class_names = $this->get_config_service()->get('sanity_check_modules');

        if (strlen($class_name))
        {
            @trigger_error('Config sanity_check_module', E_USER_DEPRECATED);

            $class_names[$class_name] = [];
        }

        return $class_names;
    }

    public function check_sanity(): bool
    {
        $class_names = $this->get_sanity_checks_to_run();
        if (!count($class_names))
        {
            return true;
        }

        $stored_values = new StoredValues($this->get_main_db(), 'sanity_check');
        $build_info = $this->get_build_info();
        $commit = $build_info['commit'];

        if ($commit == null)
        {
            // We are in live code. Prevent flooding. Only start check once per
            // five seconds.
            //
            $last_timestamp = (int) $stored_values->get_value('last_check', '0');

            if (time() - $last_timestamp < 5)
            {
                return true;
            }

            $stored_values->set_value('last_check', (string) time());
        }
        else
        {
            // Only check if this commit was not yet successfully checked
            //
            $checked = $stored_values->get_value('checked_'.$commit, '0');
            if ($checked !== '0')
            {
                return true;
            }
        }

        foreach ($class_names as $class_name => $module_config)
        {
            $sanity_check = $this->instantiate_sanity_check($class_name, $module_config);
            $result = $sanity_check->perform_checks();

            $this->verify($result, 'Sanity check failed');
        }

        // Register successful check of this commit
        //
        if ($commit !== null)
        {
            $stored_values->set_value('checked_'.$commit, '1');
        }

        return true;
    }

    public function is_authenticated(): bool
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_authentication_service()->is_authenticated();
    }

    public function authenticate(User $user): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $this->get_authentication_service()->authenticate($user);
    }

    public function deauthenticate(): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $this->get_authentication_service()->deauthenticate();
    }

    public function invalidate_sessions(int $user_id): void
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);
        $this->get_authentication_service()->invalidate_sessions($user_id);
    }

    public function get_authenticated_user(): User
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_authentication_service()->get_authenticated_user();
    }

    /**
     * @param array<string> $permissions
     */
    public function user_has_permissions(array $permissions): bool
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_authentication_service()->user_has_permissions($permissions);
    }

    /**
     * @return array<mixed>
     */
    public function get_input(): array
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_web_handler()->get_input();
    }

    /**
     * @return array<mixed>
     */
    public function get_raw_input(): array
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_web_handler()->get_raw_input();
    }

    /**
     * Get build info.
     *
     * @return array{commit: null|string, timestamp: string}
     */
    public function get_build_info(): array
    {
        @trigger_error('Deprecated. Will be removed', E_USER_DEPRECATED);

        return $this->get_debug_service()->get_build_info();
    }
}
