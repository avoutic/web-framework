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

    private static $in_verify = false;      // Only go into an assert_handler once
    private static $framework = null;
    private static $main_db = null;

    protected $input = array();
    protected $raw_input = array();
    protected $raw_post = array();

    private $main_database = null;
    private $aux_databases = array();
    private $cache = null;
    protected $blacklist = null;
    protected $security = null;
    private $messages = array();

    // Default configuration
    //
    private static $global_config = array(
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
        'cache_config' => 'main',
        'auth_mode' => 'redirect',            // redirect, www-authenticate, custom (requires auth_module)
        'auth_module' => '',
        'authenticator' => array(
            'unique_identifier' => 'email',
            'auth_required_message' => 'Authentication required. Please login.',
            'session_timeout' => 900,
        ),
        'page' => array(
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
            '404' => ''
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
            'handler_class' => 'SenderCore',
            'default_sender' => '',
        ),
    );

    static function assert_handler($file, $line, $message, $error_type, $silent = false)
    {
        $path_parts = pathinfo($file);
        $file = $path_parts['filename'];

        $error_type = WFHelpers::get_error_type_string($error_type);

        $trace = debug_backtrace();
        if (is_array($trace))
        {
            $stack = array_reverse($trace);
            $i = 0;
            foreach($stack as $entry)
            {
                $i++;

                if (in_array($entry['function'], array('assert_handler', 'verify', 'silent_verify')))
                    break;
            }

            $trace = array_slice($trace, count($trace) - $i);
            WFHelpers::scrub_state($trace);
        }

        $db_error = 'Not initialized yet';
        if (WF::get_main_db() != null)
        {
            $db_error = WF::get_main_db()->GetLastError();
            if ($db_error === false || $db_error === '')
                $db_error = 'None';
        }

        $low_info_message = "File '$file'\nLine '$line'\n";

        $framework = WF::get_framework();

        $debug_message = "File '$file'\nLine '$line'\nMessage '$message'\n";
        $debug_message.= "Last Database error: ".$db_error."\n";
        $debug_message.= "Backtrace:\n".print_r($trace, true);
        $debug_message.= "Auth:\n".print_r($framework->get_authenticated(), true);
        $debug_message.= "Input:\n".print_r($framework->get_input(), true);
        $debug_message.= "Raw Input:\n".print_r($framework->get_raw_input(), true);

        header("HTTP/1.0 500 Internal Server Error");
        var_dump(WF::get_config('debug'));
        if (WF::get_config('debug') == true)
        {
            echo "Failure information: $error_type<br/>";
            echo "<pre>";
            echo $debug_message;
            echo "</pre>";
        }
        else if (!$silent)
        {
            echo "Failure information: $error_type\n";
            echo "<pre>\n";
            echo $low_info_message;
            echo "</pre>\n";
        }

        if (WF::get_config('debug_mail') == true)
        {
            $debug_message.= "\n----------------------------\n\n";
            $debug_message.= "Server variables:\n".print_r($_SERVER, true);

            SenderCore::send_raw(
                WF::get_config('sender_core.default_sender'),
                'Assertion failed',
                "Failure information: $error_type\n\nServer: ".
                WF::get_config('server_name')."\n<pre>".$debug_message.'</pre>'
            );
        }

        if (!$silent)
            die("Oops. Something went wrong. Please retry later or contact us with the information above!\n");
    }

    static function silent_verify($bool, $message)
    {
        WF::verify($bool, $message, true);
    }

    static function verify($bool, $message, $silent = false)
    {
        if ($bool)
            return true;

        if (WF::$in_verify)
            exit();

        WF::$in_verify = true;
        $bt = debug_backtrace();
        $stack = array_reverse($bt);
        $caller = false;
        foreach($stack as $entry)
        {
            $caller = $entry;

            if (in_array($entry['function'], array('assert_handler', 'verify', 'silent_verify')))
                break;
        }

        WF::assert_handler($caller['file'], $caller['line'], $message, 'verify', $silent);
        exit();
    }

    static function blacklist_verify($bool, $reason, $severity = 1)
    {
        if ($bool)
            return;

        $framework = WF::get_framework();
        $framework->add_blacklist_entry($reason, $severity);
        exit();
    }

    function add_blacklist_entry($reason, $severity = 1)
    {
        WF::verify(false, 'No blacklist support in script mode');
    }

    static function shutdown_handler()
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
            WF::assert_handler($last_error['file'], $last_error['line'], $last_error['message'], $last_error['type']);
            break;
        default:
            WF::assert_handler($last_error['file'], $last_error['line'], $last_error['message'], $last_error['type'], true);
            exit();
        }
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

    static function get_main_db()
    {
        return WF::$main_db;
    }

    static function get_config($location = '')
    {
        if (!strlen($location))
            return WF::$global_config;

        $path = explode('.', $location);
        $part = WF::$global_config;

        foreach ($path as $step)
        {
            WF::verify(isset($part[$step]), "Missing configuration {$location}");
            $part = $part[$step];
        }

        return $part;
    }

    function get_db($tag = '')
    {
        if (!strlen($tag))
            return $this->main_database;

        WF::verify(array_key_exists($tag, $this->aux_databases), 'Database not registered');
        return $this->aux_databases[$tag];
    }

    function get_cache()
    {
        return $this->cache;
    }

    function get_security()
    {
        return $this->security;
    }

    function validate_input($filter, $item)
    {
        if (!strlen($filter))
            die("Unexpected input: \$filter not defined in validate_input().");

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
                $this->raw_input[$item][$k] = $val;
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

            $this->raw_input[$item] = $str;

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

    function init()
    {
        srand();

        $this->check_file_requirements();
        $this->merge_configs();

        // Enable debugging if requested
        //
        if (WF::get_config('debug') == true)
        {
            error_reporting(E_ALL | E_STRICT);
            ini_set("display_errors", 1);
        }
        else
            register_shutdown_function('WF::shutdown_handler');

        // Set default timezone
        //
        date_default_timezone_set(WF::get_config('timezone'));

        $this->check_config_requirements();
        $this->load_requirements();
        $this->security = new WFSecurity(WF::get_config('security'));

        if (WF::get_config('database_enabled') == true)
            $this->init_databases();

        $this->check_compatibility();

        if (WF::get_config('cache_enabled') == true)
            $this->init_cache();

        WF::$framework = $this;
    }

    private function check_file_requirements()
    {
        if (!is_file(WF::$site_includes."config.php"))
        {
            $this->exit_error('Missing requirement',
                              'One of the required files is not found on the server.');
        }

        if (!is_file(WF::$site_includes."sender_handler.inc.php"))
        {
            $this->exit_error('Sender Handler missing',
                              'One of the required files is not found on the server.');
        }
    }
    private function load_requirements()
    {
        // Check for special loads before anything else
        //
        if (WF::get_config('preload') == true)
        {
            WF::verify(file_exists(WF::$site_includes.'preload.inc.php'),
                            'preload.inc.php indicated but not present');

            require_once(WF::$site_includes.'preload.inc.php');
        }

        // Load global and site specific defines
        //
        require_once(WF::$includes."defines.inc.php");
        require_once(WF::$includes."sender_core.inc.php");
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
        WF::verify(is_array($site_config), 'Site config invalid');
        $merge_config = array_replace_recursive(WF::$global_config, $site_config);

        if (file_exists(WF::$site_includes."config_local.php"))
        {
            $local_config = require(WF::$site_includes."config_local.php");
            $merge_config = array_replace_recursive($merge_config, $local_config);
        }

        $merge_config['server_name'] = isset($_SERVER['SERVER_NAME'])?$_SERVER['SERVER_NAME']:'app';
        $merge_config['document_root'] = $_SERVER['DOCUMENT_ROOT'];

        WF::$global_config = $merge_config;
    }

    private function check_config_requirements()
    {
        // Check for required values
        //
        WF::verify(strlen(WF::get_config('sender_core.default_sender')),
                'No default_sender specified. Required for mailing verify information');

        WF::verify(strlen(WF::get_config('security.hmac_key')) > 20,
                'No or too short HMAC Key provided (Minimum 20 chars)');

        WF::verify(strlen(WF::get_config('security.crypt_key')) > 20,
                'No or too short Crypt Key provided (Minimum 20 chars)');
    }

    private function init_databases()
    {
        // Start the database connection(s)
        //
        require_once(WF::$includes.'database.inc.php');

        $this->main_database = new Database();
        $main_db_tag = WF::get_config('database_config');
        $main_config = $this->security->get_auth_config('db_config.'.$main_db_tag);

        if ($this->main_database->Connect($main_config) === false)
        {
            $this->exit_error('Database server connection failed',
                    'The connection to the database server failed.');
        }

        WF::$main_db = $this->main_database;

        // Open auxilary database connections
        //
        foreach (WF::get_config('databases') as $tag)
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
        $supported_wf_version = WF::get_config('versions.supported_framework');

        if ($required_wf_version != $supported_wf_version)
        {
            $this->exit_error('Framework version mismatch',
                    "Please make sure that this app is upgraded to support version ".
                    "{$required_wf_version} of this Framework.");
        }

        if (WF::get_config('database_enabled') != true)
            return;

        $required_wf_db_version = FRAMEWORK_DB_VERSION;
        $required_app_db_version = WF::get_config('versions.required_app_db');

        $config_values = new ConfigValues('db');
        $current_wf_db_version = $config_values->get_value('wf_db_version', '0');
        $current_app_db_version = $config_values->get_value('app_db_version', '1');

        if ($required_wf_db_version != $current_wf_db_version)
        {
            $this->exit_error('Framework Database version mismatch',
                    "Please make sure that the latest Framework database changes for version ".
                    "{$required_wf_db_version} of the scheme are applied.");
        }

        if ($required_app_db_version != $current_app_db_version)
        {
            $this->exit_error('App DB version mismatch',
                    "Please make sure that the app DB scheme matches {$required_app_db_version}.");
        }
    }

    private function init_cache()
    {
        // Start the cache connection
        //
        require_once(WF::$includes.'cache_core.inc.php');
        require_once(WF::$site_includes.'cache_handler.inc.php');

        $cache_tag = WF::get_config('cache_config');
        $cache_config = $this->security->get_auth_config('cache_config.'.$cache_tag);

        $this->cache = new Cache($cache_config);
    }

    function is_authenticated()
    {
        return false;
    }

    function authenticate($user)
    {
        WF::verify(false, 'Cannot authenticate in script mode');
    }

    function deauthenticate()
    {
        WF::verify(false, 'Cannot deauthenticate in script mode');
    }

    function invalidate_sessions($user_id)
    {
        WF::verify(false, 'Cannot invalidate sessions in script mode');
    }

    function get_authenticated($item = '')
    {
        WF::verify(false, 'Cannot retrieve authenticated data in script mode');
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
        $this->database = WF::get_main_db();
    }

    protected function get_config($path)
    {
        return WF::get_config($path);
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
        return "msg=".$this->security->urlencode_and_auth_array($msg);
    }

    // Assert related
    //
    function silent_verify($bool, $message)
    {
        WF::silent_verify($bool, $message);
    }

    function verify($bool, $message, $silent = false)
    {
        WF::verify($bool, $message, $silent);
    }

    function blacklist_verify($bool, $reason, $severity = 1)
    {
        WF::blacklist_verify($bool, $reason, $severity);
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

    protected function urlencode_and_auth_array($array)
    {
        return $this->security->urlencode_and_auth_array($array);
    }

    protected function encode_and_auth_array($array)
    {
        return $this->security->encode_and_auth_array($array);
    }

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
