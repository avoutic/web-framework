<?php
require_once(WF::$includes.'wf_helpers.inc.php');
require_once(WF::$includes.'wf_security.inc.php');

class WF
{
    static $includes = __DIR__.'/';
    static $views = __DIR__.'/../views/';
    static $site_includes = __DIR__.'/../../includes/';
    static $site_views = __DIR__.'/../../views/';
    static $site_frames = __DIR__.'/../../frames/';
    static $site_templates = __DIR__.'/../../templates/';

    private static $framework = null;
    private static $main_db = null;         // Only for DataCore and ConfigValues abstraction
    private static $static_cache = null;    // Only for DataCore and ConfigValues abstraction

    protected $input = array();
    protected $raw_input = array();
    protected $raw_post = array();

    private $in_verify = 0;             // Only go into an assert_handler for a maximum amount of times
    private $debug_message = '';
    private $debug_data = '';
    private $low_info_message = '';

    private $initialized = false;
    private $main_database = null;
    private $aux_databases = array();
    private $cache = null;
    protected $blacklist = null;
    protected $security = null;
    private $messages = array();

    private $check_app_db_version = true;

    // Default configuration
    //
    private $global_config = array(
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
            'mods' => array(),              // Should at least contain class, and include_file of mod!
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

    static function assert_handler($file, $line, $message, $error_type, $silent = false)
    {
         $framework = WF::get_framework();
         $framework->internal_assert_handler($file, $line, $message, $error_type, $silent);
    }

    function internal_assert_handler($file, $line, $message, $error_type, $silent = false)
    {
        $path_parts = pathinfo($file);
        $file = $path_parts['filename'];

        $error_type = WFHelpers::get_error_type_string($error_type);

        $trace = debug_backtrace(0);
        $stack = array();
        $stack_condensed = '';

        if (is_array($trace))
        {
            $skipping = true;

            foreach($trace as $entry)
            {
                if ($skipping &&
                    in_array($entry['function'], array('internal_assert_handler', 'assert_handler',
                                                       'internal_verify', 'verify',
                                                       'silent_verify')))
                {
                    continue;
                }

                $skipping = false;

                if (in_array($entry['function'], array('exit_send_error', 'exit_error')))
                    unset($entry['args']);

                $stack_condensed .= $entry['file'].'('.$entry['line'].'): '.
                                    $entry['class'].$entry['type'].$entry['function']."()\n";
                array_push($stack, $entry);
            }

            WFHelpers::scrub_state($stack);
        }

        $this->low_info_message .= "File '$file'\nLine '$line'\n";

        $db_error = 'Not initialized yet';
        $auth_data = 'Not authenticated';
        $input_data = '';
        $raw_input_data = '';

        $main_db = $this->get_db();

        if ($main_db != null)
        {
            $db_error = $main_db->GetLastError();
            if ($db_error === false || $db_error === '')
                $db_error = 'None';
        }

        if ($this->is_authenticated())
        {
            $auth_array = $this->get_authenticated();
            WFHelpers::scrub_state($auth_array);

            $auth_data = print_r($auth_array, true);
        }

        // Filter out error_message to prevent recursion
        //
        $inputs = $this->get_input();
        unset($inputs['error_message']);
        $input_data = print_r($inputs, true);

        $raw_input_data = print_r($this->get_raw_input(), true);

        $this->debug_message .= "File '$file'\nLine '$line'\nMessage '$message'\n";

        $this->debug_data = "\n";
        $this->debug_data .= "Condensed backtrace:\n".$stack_condensed."\n";
        $this->debug_data .= "Last Database error: ".$db_error."\n\n";
        $this->debug_data .= "Input:\n".$input_data."\n";
        $this->debug_data .= "Raw Input:\n".$raw_input_data."\n";
        $this->debug_data .= "Auth:\n".$auth_data."\n";
        $this->debug_data .= "Backtrace:\n".print_r($stack, true);

        if ($this->initialized && $this->internal_get_config('debug_mail') == true)
        {
            // If available and configured, send a debug e-mail with server variables as well
            //
            SenderCore::send_raw(
                $this->internal_get_config('sender_core.assert_recipient'),
                'Assertion failed',
                "Failure information: $error_type\n\nServer: ".
                $this->internal_get_config('server_name')."\n".
                $this->debug_message.
                $this->debug_data.
                "\n----------------------------\n\n".
                "Server variables:\n".print_r($_SERVER, true)
            );
        }

        if ($this->internal_get_config('debug') == true)
        {
            $this->exit_error(
                "Oops, something went wrong",
                "Debug information: $error_type<br/>".
                "<pre>".
                $this->debug_message.
                $this->debug_data.
                "</pre>"
            );
        }
        else if (!$silent)
        {
            $this->exit_error(
                "Oops, something went wrong",
                "Failure information: $error_type\n".
                "<pre>\n".
                $this->low_info_message.
                "</pre>\n"
            );
        }
        else
            exit();
    }

    static function silent_verify($bool, $message)
    {
        WF::verify($bool, $message, true);
    }

    static function verify($bool, $message, $silent = false)
    {
        $framework = WF::get_framework();
        $framework->internal_verify($bool, $message, $silent);
    }

    function internal_verify($bool, $message, $silent = false)
    {
        if ($bool)
            return true;

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
        $caller = false;

        foreach($stack as $entry)
        {
            $caller = $entry;

            if (in_array($entry['function'], array('internal_assert_handler', 'assert_handler',
                                                   'internal_verify', 'verify', 'silent_verify')))
                continue;

            break;
        }

        $this->internal_assert_handler($caller['file'], $caller['line'], $message, 'verify', $silent);
        exit();
    }

    static function blacklist_verify($bool, $reason, $severity = 1)
    {
        $framework = WF::get_framework();
        $framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    function internal_blacklist_verify($bool, $reason, $severity = 1)
    {
        if ($bool)
            return;

        $this->add_blacklist_entry($reason, $severity);
        exit();
    }

    function add_blacklist_entry($reason, $severity = 1)
    {
        $this->internal_verify(false, 'No blacklist support in script mode');
    }

    static function shutdown_handler()
    {
        $framework = WF::get_framework();
        $framework->internal_shutdown_handler();
    }

    function internal_shutdown_handler()
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
                                  $last_error['message'], $last_error['type']);
            break;
        default:
            $this->internal_assert_handler($last_error['file'], $last_error['line'],
                                  $last_error['message'], $last_error['type'], true);
            break;
        }

        // Don't trigger other handlers after this call
        //
        exit();
    }

    protected function exit_error($short_message, $message)
    {
        print('Fatal error: '.$short_message.PHP_EOL);
        print($message.PHP_EOL);
        exit();
    }

    static function get_framework()
    {
        return WF::$framework;
    }

    static function get_web_handler()
    {
        WF::verify(get_class(WF::$framework) == 'WFWebHandler', 'Not started as WFWebHandler');
        return WF::$framework;
    }

    static function get_config($location = '')
    {
        $framework = WF::get_framework();
        return $framework->internal_get_config($location);
    }

    function internal_get_config($location = '')
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

    function get_db($tag = '')
    {
        if (!strlen($tag))
            return $this->main_database;

        $this->internal_verify(array_key_exists($tag, $this->aux_databases), 'Database not registered');

        return $this->aux_databases[$tag];
    }

    // Only relevant for DataCore and ConfigValues to retrieve main database in static functions
    //
    static function get_main_db()
    {
        return WF::$main_db;
    }

    function get_cache()
    {
        return $this->cache;
    }

    // Only relevant for DataCore and ConfigValues to retrieve main database in static functions
    //
    static function get_static_cache()
    {
        return WF::$static_cache;
    }

    function get_security()
    {
        return $this->security;
    }

    function validate_input($filter, $item)
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
            else if (isset($_PUT[$item]))
                $info = $_PUT[$item];
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
            else if (isset($_PUT[$item]))
                $str = $_PUT[$item];
            else if (isset($_GET[$item]))
                $str = $_GET[$item];

            $this->raw_input[$item] = trim($str);

            if (preg_match("/^\s*$filter\s*$/m", $str))
                $this->input[$item] = trim($str);
        }
    }

    function get_messages()
    {
        return $this->messages;
    }

    function add_message($type, $message, $extra_message)
    {
        array_push($this->messages, array(
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extra_message,
        ));
    }

    function skip_app_db_version_check()
    {
        $this->check_app_db_version = false;
    }

    function init()
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
            ini_set("display_errors", 1);
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

    private function check_file_requirements()
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

    private function load_requirements()
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
        require_once(WF::$includes."config_values.inc.php");

        if (is_file(WF::$site_includes."site_defines.inc.php"))
            include_once(WF::$site_includes."site_defines.inc.php");
    }

    private function merge_configs()
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

        $merge_config['server_name'] = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'app';
        $merge_config['document_root'] = $_SERVER['DOCUMENT_ROOT'];

        $this->global_config = $merge_config;
    }

    private function check_config_requirements()
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

    private function init_databases()
    {
        // Start the database connection(s)
        //
        require_once(WF::$includes.'database.inc.php');

        $this->main_database = new Database();
        WF::$main_db = $this->main_database;

        $main_db_tag = $this->internal_get_config('database_config');
        $main_config = $this->security->get_auth_config('db_config.'.$main_db_tag);

        if ($this->main_database->Connect($main_config) === false)
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

            if ($database->Connect($tag_config) === false)
            {
                $this->exit_error('Databases server connection failed',
                        'The connection to the database server failed.');
            }

            $this->aux_databases[$tag] = $database;
        }
    }

    private function check_compatibility()
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

        $config_values = new ConfigValues('db');
        $current_wf_db_version = $config_values->get_value('wf_db_version', '0');
        $current_app_db_version = $config_values->get_value('app_db_version', '1');

        if ($required_wf_db_version != $current_wf_db_version)
        {
            $this->exit_error('Framework Database version mismatch',
                    "Please make sure that the latest Framework database changes for version ".
                    "{$required_wf_db_version} of the scheme are applied.");
        }

        if ($this->check_app_db_version && $required_app_db_version != $current_app_db_version)
        {
            $this->exit_error('App DB version mismatch',
                    "Please make sure that the app DB scheme matches {$required_app_db_version}.");
        }
    }

    private function init_cache()
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
            $this->cache = new NullCache();
            WF::$static_cache = $this->cache;
        }
    }

    function is_authenticated()
    {
        return false;
    }

    function authenticate($user)
    {
        $this->internal_verify(false, 'Cannot authenticate in script mode');
    }

    function deauthenticate()
    {
        $this->internal_verify(false, 'Cannot deauthenticate in script mode');
    }

    function invalidate_sessions($user_id)
    {
        $this->internal_verify(false, 'Cannot invalidate sessions in script mode');
    }

    function get_authenticated($item = '')
    {
        $this->internal_verify(false, 'Cannot retrieve authenticated data in script mode');
    }

    function user_has_permissions($permissions)
    {
        return false;
    }

    function get_input()
    {
        return $this->input;
    }

    function get_raw_input()
    {
        return $this->raw_input;
    }
}

class FrameworkCore
{
    protected $cache;
    protected $framework;
    private $security;
    private $database;

    function __construct()
    {
        $this->framework = WF::get_framework();
        $this->security = $this->framework->get_security();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
    }

    function __serialize()
    {
        return array();
    }

    function __unserialize($arr)
    {
        $this->framework = WF::get_framework();
        $this->security = $this->framework->get_security();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
    }

    protected function get_config($path)
    {
        return $this->framework->internal_get_config($path);
    }

    // Database related
    //
    protected function get_db($tag = '')
    {
        return $this->framework->get_db($tag);
    }

    protected function query($query, $params)
    {
        return $this->database->Query($query, $params);
    }

    protected function insert_query($query, $params)
    {
        return $this->database->InsertQuery($query, $params);
    }

    // Input related
    //
    protected function get_input()
    {
        return $this->framework->get_input();
    }

    protected function get_raw_input()
    {
        return $this->framework->get_raw_input();
    }

    // Message related
    //
    protected function get_messages()
    {
        return $this->framework->get_messages();
    }

    protected function add_message($mtype, $message, $extra_message = '')
    {
        $this->framework->add_message($mtype, $message, $extra_message);
    }

    protected function get_message_for_url($mtype, $message, $extra_message = '')
    {
        $msg = array('mtype' => $mtype, 'message' => $message, 'extra_message' => $extra_message);
        return "msg=".$this->security->encode_and_auth_array($msg);
    }

    // Assert related
    //
    function silent_verify($bool, $message)
    {
        WF::silent_verify($bool, $message);
    }

    function verify($bool, $message, $silent = false)
    {
        $this->framework->internal_verify($bool, $message, $silent);
    }

    function blacklist_verify($bool, $reason, $severity = 1)
    {
        $this->framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    // Security related
    //
    protected function get_auth_config($key_file)
    {
        return $this->security->get_auth_config($key_file);
    }

    protected function add_blacklist_entry($reason, $severity = 1)
    {
        $this->framework->add_blacklist_entry($reason, $severity);
    }

    // Deprecated (Remove for v4)
    //
    protected function urlencode_and_auth_array($array)
    {
        return $this->security->encode_and_auth_array($array);
    }

    protected function encode_and_auth_array($array)
    {
        return $this->security->encode_and_auth_array($array);
    }

    // Deprecated (Remove for v4)
    //
    protected function urldecode_and_verify_array($str)
    {
        return $this->security->urldecode_and_verify_array($str);
    }

    protected function decode_and_verify_array($str)
    {
        return $this->security->decode_and_verify_array($str);
    }

    // Authentication related
    //
    protected function authenticate($user)
    {
        return $this->framework->authenticate($user);
    }

    protected function deauthenticate()
    {
        return $this->framework->deauthenticate();
    }

    protected function invalidate_sessions($user_id)
    {
        return $this->framework->invalidate_sessions($user_id);
    }

    protected function is_authenticated()
    {
        return $this->framework->is_authenticated();
    }

    protected function get_authenticated($field = '')
    {
        return $this->framework->get_authenticated($field);
    }

    protected function user_has_permissions($permissions)
    {
        return $this->framework->user_has_permissions($permissions);
    }
};
?>
