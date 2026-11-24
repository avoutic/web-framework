<?php

namespace WebFramework\Core;

// Default configuration
//
return [
    'debug' => env('DEBUG', false),
    'production' => env('PRODUCTION', false),
    'preload' => false,                 /* true to load src/Preload.php during bootstrap, or name of file to
                                         * load relative to appDir
                                         */
    'timezone' => env('TIMEZONE', 'UTC'),
    'logging' => [
        'channels' => [
            'default' => 'channels.default',
            'exception' => 'channels.exception',
        ],
    ],
    'http_mode' => 'https',
    'base_url' => '',                    // Add a base_url to be used in external urls
    'session' => [
        /* Options for the PHP session, on top of the ones that follow from other settings
         * (name, lifetime, path, domain, secure and httponly). An option that is not one of
         * those is set as a PHP 'session.*' setting, for example save_path. Set an option to
         * null to leave it out.
         */
        'options' => [
            'cookie_samesite' => 'Lax',  // SameSite attribute of the session cookie: Lax, Strict, None or '' for no attribute
        ],
    ],
    'latte' => [
        'tmp_dir' => '/tmp/latte',       // Directory for the compiled templates. Use a directory that only your
        // application can write to
    ],
    'json_response_paths' => [],         /* Path prefixes (such as '/api/') that always get JSON error and
                                          * authentication responses, whatever headers the client sends
                                          */
    'sanity_check_modules' => [],        /* Associative array with application sanity check class names with
                                          * full namespace as key and their config array as the value
                                          */
    'authenticator' => [
        'unique_identifier' => 'email',
        'session_timeout' => 900,
    ],
    'security' => [
        'auth_dir' => '/config/auth',   // Relative directory with auth configuration files
        'blacklist' => [
            'trigger_period' => 14400,  // Period to consider for blacklisting (default: 4 hours)
            'store_period' => 2592000,  // Period to keep entries (default: 30 days)
            'threshold' => 25,          // Points before blacklisting occurs (default: 25)
        ],
        'trusted_proxies' => [],        // Addresses and CIDR ranges of the proxies in front of the application
        'hash' => 'sha256',
        'hmac_key' => env('HMAC_KEY', ''),
        'crypt_key' => env('CRYPT_KEY', ''),
        'recaptcha' => [
            'site_key' => env('RECAPTCHA_SITE_KEY', ''),
            'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
        ],
        'email_verification' => [
            'validity_period_days' => 30,   // Days that email verification remains valid (default: 30)
            'code_length' => 6,             // Length of verification code (default: 6)
            'code_expiry_minutes' => 15,    // Minutes until code expires (default: 15)
            'max_attempts' => 5,            // Maximum verification attempts per code (default: 5)
            'resend_cooldown_seconds' => 60, // Seconds before a new code can be requested (default: 60)
        ],
    ],
    // Action classes to execute for showing graceful error pages
    'error_handlers' => [
        '403' => null,
        '404' => null,
        '405' => null,
        '500' => null,
        'blacklisted' => null,
    ],
    'actions' => [
        'login' => [
            'location' => '/login',                 // Location of the login page
            'after_verify' => '/login/verify',      // Location to redirect to after verifying
            'default_return_page' => '/',           // Default return page
            'template_name' => 'Login.latte',       // Template name for login page
        ],
        'reset_password' => [
            'location' => '/reset-password',            // Location of the reset password page
            'after_verify' => '/reset-password/verify', // Location of the reset password page
            'template_name' => 'ResetPassword.latte',   // Template name for reset password page
        ],
        'change_password' => [
            'location' => '/change-password',          // Location of the change password page
            'return_page' => '/',                      // Location to redirect after changing password
            'template_name' => 'ChangePassword.latte', // Template name for change password page
        ],
        'change_email' => [
            'location' => '/change-email',            // Location of the change email page
            'after_verify' => '/change-email/verify', // Location of the change email verify page
            'return_page' => '/',                     // Location to redirect after changing email
            'template_name' => 'ChangeEmail.latte',   // Template name for change email page
        ],
        'register' => [
            'location' => '/register',            // Location of the register page
            'after_verify' => '/register/verify', // Location to redirect to after verifying
            'return_page' => '/',                 // Default page to redirect to after verifying
            'template_name' => 'Register.latte',  // Template name for register page
        ],
        'verify' => [
            'location' => '/verify',               // Location of the verify page
            'locations' => [                       // Location of the verify page per action type,
                'login' => null,                   // null to use 'location'
                'register' => null,
                'reset_password' => null,
                'change_email' => null,
            ],
            'resend_location' => '/verify/resend', // Location of the verify resend page
            'templates' => [                       // Template names per action type
                'login' => 'Verify.latte',
                'register' => 'Verify.latte',
                'reset_password' => 'Verify.latte',
                'change_email' => 'Verify.latte',
            ],
        ],
    ],
    // Definition files to be loaded
    // What ends up in error reports, which are mailed, logged and shown in debug mode
    'error_report' => [
        // Request headers whose name contains one of these parts are scrubbed (case-insensitive)
        'scrub_headers' => ['authorization', 'cookie', 'token', 'secret', 'key'],
        // Inputs (query, post and JSON) whose key contains one of these parts are scrubbed (case-insensitive)
        'scrub_inputs' => ['password', 'passwd', 'secret', 'token', 'authorization', 'code'],
        // Server params that are reported, all others are left out
        'server_params' => [
            'REQUEST_METHOD',
            'REQUEST_URI',
            'QUERY_STRING',
            'REQUEST_SCHEME',
            'SERVER_PROTOCOL',
            'HTTPS',
            'SERVER_NAME',
            'SERVER_PORT',
            'HTTP_HOST',
            'REMOTE_ADDR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_USER_AGENT',
            'HTTP_REFERER',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'CONTENT_TYPE',
            'CONTENT_LENGTH',
            'REQUEST_TIME',
        ],
    ],
    'definition_files' => [
        '/vendor/avoutic/web-framework/definitions/definitions.php',
        '/definitions/app_definitions.php',
    ],
    'middlewares' => [
        'pre_routing' => [],                    // Middleware classes to be executed before routing
        'post_routing' => [],                   // Middleware classes to be executed after routing
    ],
    'routes' => [],                             // The route classes to explicitly load
    'sender_core' => [
        'default_sender' => '',                 // Default sender email address
        'assert_recipient' => '',               // Recipient email address for assertions
    ],
    'translations' => [
        'default_language' => 'en',             // Default language for translations
        // Directories containing translation files
        'directories' => [
            '/vendor/avoutic/web-framework/translations',
        ],
    ],
    'user_mailer' => [
        // Template overrides for UserMailer
        'template_overrides' => [
            'email-verification-code' => 'email-verification-code',
            'change-email-verification-code' => 'change-email-verification-code',
            'password-reset' => 'password-reset',
            'new-password' => 'new-password',
        ],
    ],
    'console_tasks' => [],                      /* Associative array of console tasks to register
                                                 * The key is the command name and the value is the class name
                                                 * of the task. The class must be a subclass of ConsoleTask.
                                                 */
];
