<?php
require_once(WF::$includes.'wf_helpers.inc.php');
require_once(WF::$includes.'wf_security.inc.php');

class WF
{
    static string $includes = __DIR__.'/';
    static string $views = __DIR__.'/../views/';
    static string $site_includes = __DIR__.'/../../includes/';
    static string $site_views = __DIR__.'/../../views/';
    static string $site_frames = __DIR__.'/../../frames/';
    static string $site_templates = __DIR__.'/../../templates/';

    private static WF $framework;
    private static Database $main_db;         // Only for DataCore and StoredValues abstraction
    private static CacheInterface $static_cache;    // Only for DataCore and StoredValues abstraction

    /**
     * @var array<mixed>
     */
    protected array $input = array();

    /**
     * @var array<mixed>
     */
    protected array $raw_input = array();

    /**
     * @var array<mixed>
     */
    protected array $raw_post = array();

    private int $in_verify = 0;             // Only go into an assert_handler for a maximum amount of times
    private string $debug_message = '';
    private string $debug_data = '';
    private string $low_info_message = '';

    private bool $initialized = false;
    private Database $main_database;

    /**
     * @var array<Database>
     */
    private array $aux_databases = array();
    private CacheInterface $cache;
    protected Blacklist $blacklist;
    protected WFSecurity $security;

    /**
     * @var array<array{mtype: string, message: string, extra_message: string}>
     */
    private array $messages = array();

    private bool $check_app_db_version = true;

    // Default configuration
    //
    /**
     * @var array<mixed>
     */
    private array $global_config = array(
        'debug' => false,
        'debug_mail' => true,
        'preload' => false,
        'timezone' => 'UTC',
        'registration' => array(
            'allow_registration' => true,
            'after_verify_page' => '/',
        ),
        'database_enabled' => false,
        'database_config' => 'main',        // main database tag.
        'databases' => array(),             // list of extra database tags to load.
                                            // files will be retrieved from 'includes/db_config.{TAG}.php'
        'versions' => array(
            'supported_framework' => -1,    // Default is always -1. App should set supported semantic
                                            // version of this framework it supports in own config.
            'required_app_db' => 1,         // Default is always 1. App should set this if it tracks its
                                            // own database version in the db.app_db_version config value
                                            // in the database and wants the framework to indicate
                                            // a mismatch between required and current value
        ),
        'site_name' => 'Unknown',
        'server_name' => '',                // Set to $_SERVER['SERVER_NAME'] or 'app' automatically
                                            // For use in URLs. Is allowed to contain colon with port
                                            // number at the end.
        'host_name' => '',                  // Set to $_SERVER['SERVER_NAME'] or 'app' automatically
                                            // Pure host name. Cannot include port number information.
        'http_mode' => 'https',
        'document_root' => '',              // Set to $_SERVER['DOCUMENT_ROOT'] automatically
        'cache_enabled' => false,
        'auth_mode' => 'redirect',            // redirect, www-authenticate, custom (requires auth_module)
        'auth_module' => '',
        'authenticator' => array(
            'unique_identifier' => 'email',
            'auth_required_message' => 'Authentication required. Please login.',
            'session_timeout' => 900,
        ),
        'page' => array(
            'base_url' => '',               // Add a base_url to be used in templates
            'default_frame_file' => 'default_frame.inc.php',
            'default_page' => 'main',
        ),
        'security' => array(
            'blacklist' => array(
                'enabled' => true,
                'trigger_period' => 14400,  // Period to consider for blacklisting (default: 4 hours)
                'store_period' => 2592000,  // Period to keep entries (default: 30 days)
                'threshold' => 25,          // Points before blacklisting occurs (default: 25)
            ),
            'hash' => 'sha256',
            'hmac_key' =>  '',
            'crypt_key' => '',
            'recaptcha' => array(
                'site_key' => '',
                'secret_key' => '',
            ),
        ),
        'error_handlers' => array(
            '403' => '',
            '404' => '',
            '500' => '',
        ),
        'pages' => array(
            'login' => array(
                'location' => '/login',
                'send_verify_page' => '/send-verify',
                'verify_page' => '/verify',
                'after_verify_page' => '/',
                'default_return_page' => '/',
                'bruteforce_protection' => true,
            ),
            'forgot_password' => array(
                'location' => '/forgot-password',
                'reset_password_page' => '/reset-password',
            ),
            'change_password' => array(
                'return_page' => '/',
            ),
            'change_email' => array(
                'location' => '/change-email',
                'verify_page' => '/change-email-verify',
                'return_page' => '/',
            ),
            'send_verify' => array(
                'after_verify_page' => '/',
            ),
        ),
        'sender_core' => array(
            'handler_class' => '',
            'default_sender' => '',
            'assert_recipient' => '',
        ),
    );

    static function assert_handler(string $file, int $line, string $message, string $error_type, bool $silent = false): void
    {
         $framework = WF::get_framework();
         $framework->internal_assert_handler($file, $line, $message, $error_type, $silent);
    }

    public function internal_assert_handler(string$file, int $line, string $message, string $error_type, bool $silent = false): void
    {
        $debug_info = $this->get_debug_info($file, $line, $message);

        // In case a second verify fails, make sure this data is added to that die()
        //
        $this->low_info_message .= $debug_info['low_info_message'];
        $this->debug_message .= $debug_info['debug_message'];
        $this->debug_data .= $debug_info['debug_data'];

        $this->mail_debug_info($message, 'Assertion failed', $debug_info);

        $use_message = ($this->internal_get_config('debug') == true) ?
            $debug_info['debug_message'].$debug_info['debug_data'] : $debug_info['low_info_message'];

        $error_type = WFHelpers::get_error_type_string($error_type);

        $this->exit_error(
            "Oops, something went wrong",
            "Debug information: $error_type<br/>".
            "<br/><pre>".
            $use_message.
            "</pre>"
        );
    }

    /**
     * @param array{debug_message: string, debug_data: string, low_info_message: string} $debug_info
     */
    protected function mail_debug_info(string $message, string $error_type, array $debug_info): void
    {
        if (!$this->initialized || $this->internal_get_config('debug_mail') == false)
            return;

        $server_name = $this->internal_get_config('server_name');

        SenderCore::send_raw(
            $this->internal_get_config('sender_core.assert_recipient'),
            "{$server_name} - {$error_type}: {$message}\n\n",
            $debug_info['debug_message'].
            $debug_info['debug_data']
        );
    }

    /**
     * @param array<mixed> $trace
     * @return array{debug_message: string, debug_data: string, low_info_message: string}
     */
    protected function get_debug_info(string $file, int $line, string $message, array $trace = null): array
    {
        $info = array(
            'low_info_message' => '',
            'debug_message' => '',
            'debug_data' => '',
        );

        // Retrieve request
        //
        $server_name = $this->internal_get_config('server_name');

        $request = 'app';
        if ($server_name !== 'app')
        {
            $request = "{$_SERVER['REQUEST_METHOD']} ";
            $request .= (isset($_SERVER['REDIRECT_URL'])) ? $_SERVER['REDIRECT_URL'] : '/';
        }

        // Construct base message
        //
        $info['low_info_message'] = <<<TXT
File: {$file}
Line: {$line}

TXT;

        $info['debug_message'] = <<<TXT
File: {$file}
Line: {$line}
Message: {$message}

Server: {$server_name}
Request: {$request}

TXT;

        // Construct stack trace
        //
        if ($trace === null)
            $trace = debug_backtrace(0);

        $stack = array();
        $stack_condensed = '';

        if (is_array($trace))
        {
            $skipping = true;

            foreach($trace as $entry)
            {
                if ($skipping &&
                    in_array($entry['function'], array(
                        'get_debug_info',
                        'internal_assert_handler',
                        'assert_handler',
                        'internal_verify',
                    )))
                {
                    continue;
                }

                $skipping = false;

                if (in_array($entry['function'], array('exit_send_error', 'exit_error')))
                    unset($entry['args']);

                $stack_condensed .= $entry['file'].'('.$entry['line'].'): ';

                if (isset($entry['class']))
                    $stack_condensed .= $entry['class'].$entry['type'];

                $stack_condensed .= $entry['function']."()\n";

                array_push($stack, $entry);
            }

            WFHelpers::scrub_state($stack);
        }
        $stack_fmt = print_r($stack, true);

        // Retrieve database error
        //
        $db_error = 'Not initialized yet';
        $main_db = $this->get_db();
        if ($main_db != null)
        {
            $db_error = $main_db->get_last_error();
            if ($db_error === '')
                $db_error = 'None';
        }

        // Retrieve auth data
        //
        $auth_data = 'Not authenticated';
        if ($this->is_authenticated())
        {
            $auth_array = $this->get_authenticated();
            WFHelpers::scrub_state($auth_array);

            $auth_data = print_r($auth_array, true);
        }

        // Retrieve inputs
        //
        $inputs = $this->get_input();
        unset($inputs['error_message']);
        $input_data = print_r($inputs, true);

        $raw_input_data = print_r($this->get_raw_input(), true);

        $server_info = $_SERVER;
        $server_info['HTTP_COOKIE'] = 'scrubbed';
        $server_fmt = print_r($server_info, true);

        // Construct debug data
        //
        $info['debug_data'] = <<<TXT

Condensed backtrace:
{$stack_condensed}
Last Database error: {$db_error}

Input: {$input_data}
Raw Input: {$raw_input_data}
Auth: {$auth_data}
Backtrace:
{$stack_fmt}
{$server_fmt}
TXT;

        return $info;
    }

    static function verify(bool|int $bool, string $message, bool $silent = false): void
    {
        $framework = WF::get_framework();
        $framework->internal_verify($bool, $message, $silent);
    }

    public function internal_verify(bool|int $bool, string $message, bool $silent = false): void
    {
        if ($bool)
            return;

        if ($this->in_verify > 2)
        {
            print('<pre>');
            print($this->debug_message.PHP_EOL);
            print($this->debug_data.PHP_EOL);
            print('</pre>');

            die('2 deep into verifications.. Aborting.');
        }

        $this->in_verify++;
        $stack = debug_backtrace(0);
        $caller = $this->find_caller($stack, array('internal_assert_handler', 'assert_handler',
                                                   'internal_verify'));

        $this->internal_assert_handler($caller['file'], $caller['line'], $message, 'verify', $silent);
        exit();
    }

    /**
     * @param array<mixed> $stack
     * @param array<string> $exclude_functions
     * @return array<mixed>
     */
    private function find_caller(array $stack, array $exclude_functions): array
    {
        $caller = false;

        foreach ($stack as $entry)
        {
            $caller = $entry;

            if (in_array($entry['function'], $exclude_functions))
                continue;

            break;
        }

        return $caller;
    }

    // Send a triggered error message but continue running
    //
    /**
     * @param array<mixed> $stack
     */
    static function report_error(string $message, array $stack = null): void
    {
        $framework = WF::get_framework();
        $framework->internal_report_error($message, $stack);
    }

    /**
     * @param array<mixed> $stack
     */
    public function internal_report_error(string $message, array $stack = null): void
    {
        // Cannot report if we cannot mail
        //
        if ($this->internal_get_config('debug_mail') == false)
            return;

        if ($stack === null)
            $stack = debug_backtrace(0);

        $caller = $this->find_caller($stack, array('internal_report_error'));

        $debug_info = $this->get_debug_info($caller['file'], $caller['line'], $message, $stack);

        $this->mail_debug_info($message, 'Error reported', $debug_info);
    }

    static function blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        $framework = WF::get_framework();
        $framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    public function internal_blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        if ($bool)
            return;

        $this->add_blacklist_entry($reason, $severity);
        exit();
    }

    public function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        $this->internal_verify(false, 'No blacklist support in script mode');
    }

    static function shutdown_handler(): void
    {
        $framework = WF::get_framework();
        $framework->internal_shutdown_handler();
    }

    public function internal_shutdown_handler(): void
    {
        $last_error = error_get_last();
        if (!$last_error)
            return;

        if ($last_error['type'] == E_NOTICE && $last_error['file'] == 'adodb-mysqli.inc')
            return;

        switch($last_error['type'])
        {
        case E_ERROR:
        case E_PARSE:
        case E_CORE_ERROR:
        case E_CORE_WARNING:
        case E_COMPILE_ERROR:
        case E_COMPILE_WARNING:
            $this->internal_assert_handler($last_error['file'], $last_error['line'],
                                  $last_error['message'], (string) $last_error['type']);
            break;
        default:
            $this->internal_assert_handler($last_error['file'], $last_error['line'],
                                  $last_error['message'], (string) $last_error['type'], true);
            break;
        }

        // Don't trigger other handlers after this call
        //
        exit();
    }

    protected function exit_error(string $short_message, string $message): void
    {
        print('Fatal error: '.$short_message.PHP_EOL);
        print($message.PHP_EOL);
        exit();
    }

    static function get_framework(): WF
    {
        return WF::$framework;
    }

    /**
     * @return WFWebHandler
     */
    static function get_web_handler(): WFWebHandler
    {
        WF::verify(WF::$framework instanceof WFWebHandler, 'Not started as WFWebHandler');
        return WF::$framework;
    }

    /**
     * @return mixed
     */
    static function get_config(string $location = ''): mixed
    {
        $framework = WF::get_framework();
        return $framework->internal_get_config($location);
    }

    /**
     * @return mixed
     */
    public function internal_get_config(string $location = ''): mixed
    {
        if (!strlen($location))
            return $this->global_config;

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
            return $this->main_database;

        $this->internal_verify(array_key_exists($tag, $this->aux_databases), 'Database not registered');

        return $this->aux_databases[$tag];
    }

    // Only relevant for DataCore and StoredValues to retrieve main database in static functions
    //
    static function get_main_db(): Database
    {
        return WF::$main_db;
    }

    public function get_cache(): CacheInterface
    {
        return $this->cache;
    }

    // Only relevant for DataCore and StoredValues to retrieve main database in static functions
    //
    static function get_static_cache(): CacheInterface
    {
        return WF::$static_cache;
    }

    public function get_security(): WFSecurity
    {
        return $this->security;
    }

    public function validate_input(string $filter, string $item): void
    {
        $this->internal_verify(strlen($filter), 'No filter provided');

        if (substr($item, -2) == '[]')
        {
            $item = substr($item, 0, -2);

            // Expect multiple values
            //
            $info = array();
            $this->input[$item] = array();
            $this->raw_input[$item] = array();

            if (isset($this->raw_post[$item]))
                $info = $this->raw_post[$item];
            else if (isset($_POST[$item]))
                $info = $_POST[$item];
            else if (isset($_GET[$item]))
                $info = $_GET[$item];

            foreach ($info as $k => $val)
            {
                $this->raw_input[$item][$k] = trim($val);
                if (preg_match("/^\s*$filter\s*$/m", $val))
                    $this->input[$item][$k] = trim($val);
            }
        }
        else
        {
            $str = "";
            $this->input[$item] = "";

            if (isset($this->raw_post[$item]))
                $str = $this->raw_post[$item];
            else if (isset($_POST[$item]))
                $str = $_POST[$item];
            else if (isset($_GET[$item]))
                $str = $_GET[$item];

            $this->raw_input[$item] = trim($str);

            if (preg_match("/^\s*$filter\s*$/m", $str))
                $this->input[$item] = trim($str);
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
        array_push($this->messages, array(
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extra_message,
        ));
    }

    public function skip_app_db_version_check(): void
    {
        $this->check_app_db_version = false;
    }

    public function init(): void
    {
        // Make sure static wrapper functions can work
        //
        WF::$framework = $this;

        srand();

        $this->check_file_requirements();
        $this->merge_configs();

        // Enable debugging if requested
        //
        if ($this->internal_get_config('debug') == true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set("display_errors", '1');
        }
        else
            register_shutdown_function(array($this, 'internal_shutdown_handler'));

        // Set default timezone
        //
        date_default_timezone_set($this->internal_get_config('timezone'));

        $this->check_config_requirements();
        $this->load_requirements();
        $this->security = new WFSecurity($this->internal_get_config('security'));

        if ($this->internal_get_config('database_enabled') == true)
            $this->init_databases();

        $this->check_compatibility();

        $this->init_cache();

        $this->initialized = true;
    }

    private function check_file_requirements(): void
    {
        if (!is_file(WF::$site_includes."config.php"))
        {
            $this->exit_error('Missing base requirement',
                              'One of the required files (includes/config.php) is not found on the server.');
        }

        if (!is_file(WF::$site_includes."sender_handler.inc.php"))
        {
            $this->exit_error('Sender Handler missing',
                              'One of the required files (includes/sender_handler.inc.php) is not found on the server.');
        }
    }

    private function load_requirements(): void
    {
        // Check for special loads before anything else
        //
        if ($this->internal_get_config('preload') == true)
        {
            if (!file_exists(WF::$site_includes.'preload.inc.php'))
            {
                $this->exit_error('Preload indicated but not present',
                    'The file "preload.inc.php" does not exist.');
            }

            require_once(WF::$site_includes.'preload.inc.php');
        }

        // Load global and site specific defines
        //
        require_once(WF::$includes."defines.inc.php");
        require_once(WF::$includes."sender_core.inc.php");
        if (!class_exists($this->internal_get_config('sender_core.handler_class')))
        {
            $this->exit_error('Handler class does not exist',
                              'The class configured in "sender_core.handler_class" is not provided by includes/sender_handler.inc.php.');
        }

        require_once(WF::$includes."base_logic.inc.php");
        require_once(WF::$includes."stored_values.inc.php");

        if (is_file(WF::$site_includes."site_defines.inc.php"))
            include_once(WF::$site_includes."site_defines.inc.php");
    }

    private function merge_configs(): void
    {
        // Merge configurations
        //
        $site_config = require(WF::$site_includes.'config.php');
        if (!is_array($site_config))
            $this->exit_error('Site config invalid', 'No config array found');

        $merge_config = array_replace_recursive($this->global_config, $site_config);

        if (file_exists(WF::$site_includes."config_local.php"))
        {
            $local_config = require(WF::$site_includes."config_local.php");
            $merge_config = array_replace_recursive($merge_config, $local_config);
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
                $merge_config['server_name'] = $_SERVER['SERVER_NAME'];

            if (!strlen($merge_config['host_name']))
                $merge_config['host_name'] = $_SERVER['SERVER_NAME'];
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
            $this->exit_error('No default sender specified',
                              'One of the required config values (sender_core.default_sender) is missing. '.
                              'Required for mailing verify information');
        }

        if (!strlen($this->internal_get_config('sender_core.assert_recipient')))
        {
            $this->exit_error('No assert recipient specified',
                              'One of the required config values (sender_core.assert_recipient) is missing. '.
                              'Required for mailing verify information');
        }

        if (strlen($this->internal_get_config('security.hmac_key')) < 20)
        {
            $this->exit_error('Required config value missing',
                'No or too short HMAC Key provided (Minimum 20 chars) in (security.hmac_key).');
        }

        if (strlen($this->internal_get_config('security.crypt_key')) < 20)
        {
            $this->exit_error('Required config value missing',
                'No or too short Crypt Key provided (Minimum 20 chars) in (security.crypt_key).');
        }
    }

    private function init_databases(): void
    {
        // Start the database connection(s)
        //
        require_once(WF::$includes.'database.inc.php');

        $this->main_database = new Database();
        WF::$main_db = $this->main_database;

        $main_db_tag = $this->internal_get_config('database_config');
        $main_config = $this->security->get_auth_config('db_config.'.$main_db_tag);

        if ($this->main_database->connect($main_config) === false)
        {
            $this->exit_error('Database server connection failed',
                    'The connection to the database server failed.');
        }

        // Open auxilary database connections
        //
        foreach ($this->internal_get_config('databases') as $tag)
        {
            $database = new Database();
            $tag_config = $this->security->get_auth_config('db_config.'.$tag);

            if ($database->connect($tag_config) === false)
            {
                $this->exit_error('Databases server connection failed',
                        'The connection to the database server failed.');
            }

            $this->aux_databases[$tag] = $database;
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
            $this->exit_error('No supported Framework version configured',
                    'There is no supported framework version provided in "versions.supported_framework". '.
                    'The current version is {$required_wf_version} of this Framework.');
        }

        if ($required_wf_version != $supported_wf_version)
        {
            $this->exit_error('Framework version mismatch',
                    'Please make sure that this app is upgraded to support version '.
                    '{$required_wf_version} of this Framework.');
        }

        if ($this->internal_get_config('database_enabled') != true)
            return;

        $required_wf_db_version = FRAMEWORK_DB_VERSION;
        $required_app_db_version = $this->internal_get_config('versions.required_app_db');

        $stored_values = new StoredValues('db');
        $current_wf_db_version = $stored_values->get_value('wf_db_version', '0');
        $current_app_db_version = $stored_values->get_value('app_db_version', '1');

        if ($required_wf_db_version != $current_wf_db_version)
        {
            $this->exit_error('Framework Database version mismatch',
                    "Please make sure that the latest Framework database changes for version ".
                    "{$required_wf_db_version} of the scheme are applied.");
        }

        if ($this->check_app_db_version && $required_app_db_version > $current_app_db_version)
        {
            $this->exit_error('Outdated version of the app DB',
                    "Please make sure that the app DB scheme is at least {$required_app_db_version}.");
        }
    }

    private function init_cache(): void
    {
        if ($this->internal_get_config('cache_enabled') == true)
        {
            // Start the Redis cache connection
            //
            require_once(WF::$includes.'redis_cache.inc.php');
            $cache_config = $this->security->get_auth_config('redis');

            $this->cache = new RedisCache($cache_config);
            WF::$static_cache = $this->cache;
        }
        else
        {
            // Initialize NullCache
            //
            require_once(WF::$includes.'null_cache.inc.php');
            $this->cache = new NullCache(array());
            WF::$static_cache = $this->cache;
        }
    }

    public function get_sanity_check(): SanityCheckInterface
    {
        require_once(WF::$includes.'sanity_check_interface.inc.php');
        require_once(WF::$site_includes."sanity_check.inc.php");
        $this->verify(class_exists('SanityCheck'), 'SanityCheck class not found');

        $obj = new SanityCheck();

        $this->verify($obj instanceof SanityCheckInterface, 'SanityCheck->perform_checks() not found');

        return new SanityCheck();
    }

    public function check_sanity(): bool
    {
        if (!is_file(WF::$site_includes."sanity_check.inc.php"))
            return true;

        $stored_values = new StoredValues('sanity_check');
        $build_info = $this->get_build_info();
        $commit = $build_info['commit'];

        if ($commit == null)
        {
            // We are in live code. Prevent flooding. Only start check once per minute.
            //
            $last_timestamp = (int) $stored_values->get_value('last_check', '0');

            if (time() - $last_timestamp < 60)
                return true;

            $stored_values->set_value('last_check', (string) time());
        }
        else
        {
            // Only check if this commit was not yet successfully checked
            //
            $checked = $stored_values->get_value('checked_'.$commit, '0');
            if ($checked !== '0')
                return true;
        }

        $sanity_check = $this->get_sanity_check();
        $result = $sanity_check->perform_checks();

        $this->verify($result, 'Sanity check failed');

        // Register successful check of this commit
        //
        if ($commit !== null)
            $stored_values->set_value('checked_'.$commit, '1');

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
     * Get build info
     *
     * @return array{commit: null|string, timestamp: int}
     */
    public function get_build_info(): array
    {
        if (!file_exists(__DIR__.'/../../build_commit'))
        {
            return array(
                'commit' => null,
                'timestamp' => time(),
            );
        }

        $commit = substr(file_get_contents(__DIR__.'/../../build_commit'), 0, 8);
        $build_time = file_get_contents(__DIR__.'/../../build_timestamp');

        return array(
            'commit' => $commit,
            'timestamp' => (int) $build_time,
        );
    }
}

class FrameworkCore
{
    protected CacheInterface $cache;
    protected WF $framework;
    private WFSecurity $security;
    private Database $database;

    function __construct()
    {
        $this->framework = WF::get_framework();
        $this->security = $this->framework->get_security();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
    }

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        return array();
    }

    /**
     * @param array<string> $arr
     */
    public function __unserialize(array $arr): void
    {
        $this->framework = WF::get_framework();
        $this->security = $this->framework->get_security();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
    }

    protected function get_config(string $path): mixed
    {
        return $this->framework->internal_get_config($path);
    }

    // Database related
    //
    protected function get_db(string $tag = ''): Database
    {
        return $this->framework->get_db($tag);
    }

    /**
     * @param array<bool|int|string> $params
     */
    protected function query(string $query, array $params): mixed
    {
        return $this->database->query($query, $params);
    }

    /**
     * @param array<bool|int|string> $params
     */
    protected function insert_query(string $query, array $params): false|int
    {
        return $this->database->insert_query($query, $params);
    }

    // Input related
    //
    /**
     * @return array<mixed>
     */
    protected function get_input(): array
    {
        return $this->framework->get_input();
    }

    /**
     * @return array<mixed>
     */
    protected function get_raw_input(): array
    {
        return $this->framework->get_raw_input();
    }

    // Message related
    //
    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    protected function get_messages(): array
    {
        return $this->framework->get_messages();
    }

    protected function add_message(string $mtype, string $message, string $extra_message = ''): void
    {
        $this->framework->add_message($mtype, $message, $extra_message);
    }

    protected function get_message_for_url(string $mtype, string $message, string $extra_message = ''): string
    {
        $msg = array('mtype' => $mtype, 'message' => $message, 'extra_message' => $extra_message);
        return "msg=".$this->security->encode_and_auth_array($msg);
    }

    // Assert related
    //
    /**
     * @param array<mixed> $stack
     */
    protected function report_error(string $message, array $stack = null): void
    {
        WF::report_error($message, $stack);
    }

    protected function verify(bool|int $bool, string $message, bool $silent = false): void
    {
        $this->framework->internal_verify($bool, $message, $silent);
    }

    protected function blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        $this->framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    // Security related
    //
    /**
     * @return mixed
     */
    protected function get_auth_config(string $key_file): mixed
    {
        return $this->security->get_auth_config($key_file);
    }

    protected function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        $this->framework->add_blacklist_entry($reason, $severity);
    }

    /**
     * @param array<mixed> $array
     */
    protected function encode_and_auth_array(array $array): string
    {
        return $this->security->encode_and_auth_array($array);
    }

    /**
     * @return false|array<mixed>
     */
    protected function decode_and_verify_array(string $str): array|false
    {
        return $this->security->decode_and_verify_array($str);
    }

    // Authentication related
    //
    protected function authenticate(User $user): void
    {
        $this->framework->authenticate($user);
    }

    protected function deauthenticate(): void
    {
        $this->framework->deauthenticate();
    }

    protected function invalidate_sessions(int $user_id): void
    {
        $this->framework->invalidate_sessions($user_id);
    }

    protected function is_authenticated(): bool
    {
        return $this->framework->is_authenticated();
    }

    protected function get_authenticated(string $field = ''): mixed
    {
        return $this->framework->get_authenticated($field);
    }

    /**
     * @param array<string> $permissions
     */
    protected function user_has_permissions(array $permissions): bool
    {
        return $this->framework->user_has_permissions($permissions);
    }

    // Build info
    //
    /**
     * @return array{commit: null|string, timestamp: int}
     */
    protected function get_build_info(): array
    {
        return $this->framework->get_build_info();
    }
};
?>
