<?php

namespace WebFramework\Core;

use Cache\Adapter\Redis\RedisCachePool;
use Slim\Psr7\Factory\ServerRequestFactory;

class WF
{
    private string $app_dir = '';

    private static WF $framework;
    private static Database $main_db;         // Only for DataCore and StoredValues abstraction
    private static CacheService $static_cache;    // Only for DataCore and StoredValues abstraction

    /**
     * @var array<mixed>
     */
    protected array $input = [];

    /**
     * @var array<mixed>
     */
    protected array $raw_input = [];

    /**
     * @var array<mixed>
     */
    protected array $raw_post = [];

    protected bool $initialized = false;
    private Database $main_database;

    /**
     * @var array<Database>
     */
    private array $aux_databases = [];
    private CacheService $cache;

    // Services
    //
    protected ?AssertService $assert_service = null;
    protected ?Security\BlacklistService $blacklist_service = null;
    protected ?Security\CsrfService $csrf_service = null;
    protected ?DebugService $debug_service = null;
    protected ?MailService $mail_service = null;
    protected ?ReportFunction $report_function = null;
    protected ?Security\ConfigService $secure_config_service = null;
    protected ?Security\ProtectService $protect_service = null;

    /**
     * @var array<array{mtype: string, message: string, extra_message: string}>
     */
    private array $messages = [];

    /**
     * @var array<string>
     */
    private array $configs = [
        '/includes/config.php',
        '?/includes/config_local.php',
    ];

    private bool $check_db = true;
    private bool $check_app_db_version = true;
    private bool $check_wf_db_version = true;

    // Default configuration
    //
    /**
     * @var array<mixed>
     */
    private array $global_config = [
        'debug' => false,
        'debug_mail' => true,
        'preload' => false,
        'timezone' => 'UTC',
        'registration' => [
            'allow_registration' => true,
            'after_verify_page' => '/',
        ],
        'database_enabled' => false,
        'database_config' => 'main',        // main database tag.
        'databases' => [],             // list of extra database tags to load.
        // files will be retrieved from 'includes/db_config.{TAG}.php'
        'versions' => [
            'supported_framework' => -1,    // Default is always -1. App should set supported semantic
            // version of this framework it supports in own config.
            'required_app_db' => 1,         // Default is always 1. App should set this if it tracks its
            // own database version in the db.app_db_version config value
            // in the database and wants the framework to indicate
            // a mismatch between required and current value
        ],
        'site_name' => 'Unknown',
        'server_name' => '',                // Set to $_SERVER['SERVER_NAME'] or 'app' automatically
        // For use in URLs. Is allowed to contain colon with port
        // number at the end.
        'host_name' => '',                  // Set to $_SERVER['SERVER_NAME'] or 'app' automatically
        // Pure host name. Cannot include port number information.
        'http_mode' => 'https',
        'base_url' => '',                   // Add a base_url to be used in external urls
        'document_root' => '',              // Set to $_SERVER['DOCUMENT_ROOT'] automatically
        'cache_enabled' => false,
        'auth_mode' => 'redirect',          // redirect, www-authenticate, custom (requires auth_module)
        'auth_module' => '',                // class name with full namespace
        'sanity_check_module' => '',        // class name with full namespace (Deprecated)
        'sanity_check_modules' => [],       // associative array with class names with full namespace as key
                                            // and their config array as the value
        'sanity_check' => [
            'required_auth' => [],          // Auth files that should be present
        ],
        'authenticator' => [
            'unique_identifier' => 'email',
            'auth_required_message' => 'Authentication required. Please login.',
            'session_timeout' => 900,
        ],
        'security' => [
            'auth_dir' => '/includes/auth', // Relative directory with auth configuration files
            'blacklist' => [
                'enabled' => true,
                'trigger_period' => 14400,  // Period to consider for blacklisting (default: 4 hours)
                'store_period' => 2592000,  // Period to keep entries (default: 30 days)
                'threshold' => 25,          // Points before blacklisting occurs (default: 25)
            ],
            'hash' => 'sha256',
            'hmac_key' => '',
            'crypt_key' => '',
            'recaptcha' => [
                'site_key' => '',
                'secret_key' => '',
            ],
        ],
        'error_handlers' => [
            '403' => '',
            '404' => '',
            '500' => '',
        ],
        'actions' => [
            'default_action' => 'Main.html_main',
            'default_frame_file' => 'default_frame.inc.php',
            'app_namespace' => 'App\\Actions\\',
            'login' => [
                'location' => '/login',
                'send_verify_page' => '/send-verify',
                'verify_page' => '/verify',
                'after_verify_page' => '/',
                'default_return_page' => '/',
                'bruteforce_protection' => true,
            ],
            'forgot_password' => [
                'location' => '/forgot-password',
                'reset_password_page' => '/reset-password',
            ],
            'change_password' => [
                'return_page' => '/',
            ],
            'change_email' => [
                'location' => '/change-email',
                'verify_page' => '/change-email-verify',
                'return_page' => '/',
            ],
            'send_verify' => [
                'after_verify_page' => '/',
            ],
        ],
        'sender_core' => [
            'handler_class' => '',
            'default_sender' => '',
            'assert_recipient' => '',
        ],
    ];

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
            $this->assert_service = new AssertService(
                $this->get_debug_service(),
                $this->get_report_function(),
            );
        }

        return $this->assert_service;
    }

    public function get_blacklist_service(): Security\BlacklistService
    {
        if ($this->blacklist_service === null)
        {
            if ($this->internal_get_config('security.blacklist.enabled') == true)
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
        }

        return $this->blacklist_service;
    }

    public function get_csrf_service(): Security\CsrfService
    {
        if ($this->csrf_service === null)
        {
            $this->csrf_service = new Security\CsrfService();
        }

        return $this->csrf_service;
    }

    public function get_debug_service(): DebugService
    {
        if ($this->debug_service === null)
        {
            $this->debug_service = new DebugService(
                $this,
                $this->get_config('server_name')
            );
        }

        return $this->debug_service;
    }

    public function get_report_function(): ReportFunction
    {
        if ($this->report_function === null)
        {
            $this->report_function = new MailReportFunction(
                $this->get_cache(),
                $this->get_mail_service(),
                $this->get_config('sender_core.assert_recipient'),
            );
        }

        return $this->report_function;
    }

    public function get_secure_config_service(): Security\ConfigService
    {
        if ($this->secure_config_service === null)
        {
            $this->secure_config_service = new Security\ConfigService(
                $this->get_app_dir().$this->get_config('security.auth_dir'),
            );
        }

        return $this->secure_config_service;
    }

    public function get_protect_service(): Security\ProtectService
    {
        if ($this->protect_service === null)
        {
            $this->protect_service = new Security\ProtectService(
                $this->get_config('security'),
            );
        }

        return $this->protect_service;
    }

    public function get_mail_service(): MailService
    {
        if ($this->mail_service === null)
        {
            $this->mail_service = new NullMailService();
        }

        return $this->mail_service;
    }

    public static function assert_handler(string $file, int $line, string $message, string $error_type): void
    {
        $framework = self::get_framework();
        $framework->internal_assert_handler($message, $error_type);
    }

    public function internal_assert_handler(string $message, string $error_type): void
    {
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
            $user_id = $this->get_authenticated('user_id');
        }

        $this->get_blacklist_service()->add_entry($ip, $user_id, $reason, $severity);
    }

    public static function shutdown_handler(): void
    {
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

        $this->internal_assert_handler($message, (string) $last_error['type']);

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

    public static function get_web_handler(): WFWebHandler
    {
        self::verify(self::$framework instanceof WFWebHandler, 'Not started as WFWebHandler');

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

        return $framework->internal_get_config($location);
    }

    public function internal_get_config(string $location = ''): mixed
    {
        if (!strlen($location))
        {
            return $this->global_config;
        }

        $path = explode('.', $location);
        $part = $this->global_config;

        foreach ($path as $step)
        {
            $this->internal_verify(isset($part[$step]), "Missing configuration {$location}");
            $part = $part[$step];
        }

        return $part;
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

    public function get_cache(): CacheService
    {
        return $this->cache;
    }

    // Only relevant for DataCore and StoredValues to retrieve main database in static functions
    //
    public static function get_static_cache(): CacheService
    {
        return self::$static_cache;
    }

    public function validate_input(string $filter, string $item): void
    {
        $this->internal_verify(strlen($filter), 'No filter provided');

        if (substr($item, -2) == '[]')
        {
            $item = substr($item, 0, -2);

            // Expect multiple values
            //
            $info = [];
            $this->input[$item] = [];
            $this->raw_input[$item] = [];

            if (isset($this->raw_post[$item]))
            {
                $info = $this->raw_post[$item];
            }
            elseif (isset($_POST[$item]))
            {
                $info = $_POST[$item];
            }
            elseif (isset($_GET[$item]))
            {
                $info = $_GET[$item];
            }

            foreach ($info as $k => $val)
            {
                $this->raw_input[$item][$k] = trim($val);
                if (preg_match("/^\\s*{$filter}\\s*$/m", $val))
                {
                    $this->input[$item][$k] = trim($val);
                }
            }
        }
        else
        {
            $str = '';
            $this->input[$item] = '';

            if (isset($this->raw_post[$item]))
            {
                $str = $this->raw_post[$item];
            }
            elseif (isset($_POST[$item]))
            {
                $str = $_POST[$item];
            }
            elseif (isset($_GET[$item]))
            {
                $str = $_GET[$item];
            }

            $this->raw_input[$item] = trim($str);

            if (preg_match("/^\\s*{$filter}\\s*$/m", $str))
            {
                $this->input[$item] = trim($str);
            }
        }
    }

    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function get_messages(): array
    {
        return $this->messages;
    }

    public function add_message(string $type, string $message, string $extra_message): void
    {
        $this->messages[] = [
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extra_message,
        ];
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
        $this->merge_configs($this->configs);

        // Enable debugging if requested
        //
        if ($this->internal_get_config('debug') == true)
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
        date_default_timezone_set($this->internal_get_config('timezone'));

        $this->check_config_requirements();
        $this->load_requirements();

        if ($this->internal_get_config('database_enabled') == true)
        {
            $this->init_databases();
        }

        $this->check_compatibility();

        $this->init_cache();

        $this->initialized = true;
    }

    private function check_file_requirements(): void
    {
        if (!is_file("{$this->app_dir}/includes/config.php"))
        {
            $this->exit_error(
                'Missing base requirement',
                'One of the required files (includes/config.php) is not found on the server.'
            );
        }
    }

    private function load_requirements(): void
    {
        // Check for special loads before anything else
        //
        if ($this->internal_get_config('preload') == true)
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

        if (!class_exists($this->internal_get_config('sender_core.handler_class')))
        {
            $this->exit_error(
                'Handler class does not exist',
                'The class configured in "sender_core.handler_class" cannot be found'
            );
        }
    }

    /**
     * @param array<string> $configs Config files to merge on top of each other in order.
     *                               File locations should be relative to the app dir
     *                               including leading /. If it starts with a '?' the file
     *                               does not have to be present.
     */
    private function merge_configs(array $configs): void
    {
        $merge_config = $this->global_config;

        // Merge configurations
        //
        foreach ($configs as $config_location)
        {
            if ($config_location[0] == '?')
            {
                $config_location = substr($config_location, 1);

                if (!file_exists("{$this->app_dir}{$config_location}"))
                {
                    continue;
                }
            }

            $file_config = require "{$this->app_dir}{$config_location}";
            if (!is_array($file_config))
            {
                $this->exit_error('Config invalid', "No config array found in '{$config_location}'");
            }

            $merge_config = array_replace_recursive($merge_config, $file_config);
        }

        // Force server_name and host_name to 'app' if run locally.
        // Otherwise only set dynamically to SERVER_NAME if not defined in the merged config.
        // server_name is meant to be used in urls and can contain port information.
        // host_name is meant to be used as host and cannot contain port information.
        //
        if (!isset($_SERVER['SERVER_NAME']))
        {
            $merge_config['server_name'] = 'app';
            $merge_config['host_name'] = 'app';
        }
        else
        {
            if (!strlen($merge_config['server_name']))
            {
                $merge_config['server_name'] = $_SERVER['SERVER_NAME'];
            }

            if (!strlen($merge_config['host_name']))
            {
                $merge_config['host_name'] = $_SERVER['SERVER_NAME'];
            }
        }

        $merge_config['document_root'] = $_SERVER['DOCUMENT_ROOT'];

        $this->global_config = $merge_config;
    }

    private function check_config_requirements(): void
    {
        // Check for required values
        //
        if (!strlen($this->internal_get_config('sender_core.default_sender')))
        {
            $this->exit_error(
                'No default sender specified',
                'One of the required config values (sender_core.default_sender) is missing. '.
                'Required for mailing verify information'
            );
        }

        if (!strlen($this->internal_get_config('sender_core.assert_recipient')))
        {
            $this->exit_error(
                'No assert recipient specified',
                'One of the required config values (sender_core.assert_recipient) is missing. '.
                'Required for mailing verify information'
            );
        }

        if (strlen($this->internal_get_config('security.hmac_key')) < 20)
        {
            $this->exit_error(
                'Required config value missing',
                'No or too short HMAC Key provided (Minimum 20 chars) in (security.hmac_key).'
            );
        }

        if (strlen($this->internal_get_config('security.crypt_key')) < 20)
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
        $main_db_tag = $this->internal_get_config('database_config');
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

        $this->main_database = new Database($mysql);
        self::$main_db = $this->main_database;

        // Set the Database in the DebugService after init
        //
        $this->get_debug_service()->set_database($this->main_database);

        // Open auxilary database connections
        //
        foreach ($this->internal_get_config('databases') as $tag)
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

            $this->aux_databases[$tag] = new Database($mysql);
        }
    }

    private function check_compatibility(): void
    {
        // Verify all versions for compatibility
        //
        $required_wf_version = FRAMEWORK_VERSION;
        $supported_wf_version = $this->internal_get_config('versions.supported_framework');

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

        if ($this->internal_get_config('database_enabled') != true || !$this->check_db)
        {
            return;
        }

        $required_wf_db_version = FRAMEWORK_DB_VERSION;
        $required_app_db_version = $this->internal_get_config('versions.required_app_db');

        // Check if base table is present
        //
        if (!$this->main_database->table_exists('config_values'))
        {
            $this->exit_error(
                'Database missing config_values table',
                'Please make sure that the core Framework database scheme has been applied. (by running db_init script)'
            );
        }

        $stored_values = new StoredValues('db');
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
        if ($this->internal_get_config('cache_enabled') == true)
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
            self::verify($result === true, 'Failed to connect to Redis cache');

            $cache_pool = new RedisCachePool($redis_client);

            try
            {
                // Workaround: Without trying to check something, the connection is not yet verified.
                //
                $cache_pool->hasItem('errors');
            }
            catch (\Throwable $e)
            {
                self::verify(false, 'Failed to connect to Redis cache');
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
        $class_name = $this->internal_get_config('sanity_check_module');

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
        $class_name = $this->internal_get_config('sanity_check_module');
        $class_names = $this->internal_get_config('sanity_check_modules');

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

        $stored_values = new StoredValues('sanity_check');
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
        return false;
    }

    public function authenticate(User $user): void
    {
        $this->internal_verify(false, 'Cannot authenticate in script mode');
    }

    public function deauthenticate(): void
    {
        $this->internal_verify(false, 'Cannot deauthenticate in script mode');
    }

    public function invalidate_sessions(int $user_id): void
    {
        $this->internal_verify(false, 'Cannot invalidate sessions in script mode');
    }

    public function get_authenticated(string $item = ''): mixed
    {
        $this->internal_verify(false, 'Cannot retrieve authenticated data in script mode');

        return false;
    }

    /**
     * @param array<string> $permissions
     */
    public function user_has_permissions(array $permissions): bool
    {
        return false;
    }

    /**
     * @return array<mixed>
     */
    public function get_input(): array
    {
        return $this->input;
    }

    /**
     * @return array<mixed>
     */
    public function get_raw_input(): array
    {
        return $this->raw_input;
    }

    /**
     * Get build info.
     *
     * @return array{commit: null|string, timestamp: string}
     */
    public function get_build_info(): array
    {
        if (!file_exists($this->app_dir.'/build_commit') || !file_exists($this->app_dir.'/build_timestamp'))
        {
            return [
                'commit' => null,
                'timestamp' => date('Y-m-d H:i'),
            ];
        }

        $commit = file_get_contents($this->app_dir.'/build_commit');
        $this->internal_verify($commit !== false, 'Failed to retrieve build_commit');
        $commit = substr($commit, 0, 8);

        $build_time = file_get_contents($this->app_dir.'/build_timestamp');
        $this->internal_verify($build_time !== false, 'Failed to retrieve build_timestamp');

        return [
            'commit' => $commit,
            'timestamp' => $build_time,
        ];
    }
}
