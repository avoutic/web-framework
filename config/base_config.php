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
    'database_enabled' => env('DATABASE_ENABLED', false),
    'http_mode' => 'https',
    'base_url' => '',                    // Add a base_url to be used in external urls
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
        'hash' => 'sha256',
        'hmac_key' => env('HMAC_KEY', ''),
        'crypt_key' => env('CRYPT_KEY', ''),
        'recaptcha' => [
            'site_key' => env('RECAPTCHA_SITE_KEY', ''),
            'secret_key' => env('RECAPTCHA_SECRET_KEY', ''),
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
            'location' => '/login',             // Location of the login page
            'verify_page' => '/verify',         // Location of the verify page
            'after_verify_page' => '/',         // Location to redirect after triggering verification
            'default_return_page' => '/',       // Default return page
            'bruteforce_protection' => true,    // Enable bruteforce protection
        ],
        'forgot_password' => [
            'location' => '/forgot-password',   // Location of the forgot password page
            'reset_password_page' => '/reset-password', // Location of the reset password page
        ],
        'change_password' => [
            'return_page' => '/',               // Location to redirect after changing password
        ],
        'change_email' => [
            'location' => '/change-email',      // Location of the change email page
            'verify_page' => '/change-email-verify', // Location of the change email verify page
            'return_page' => '/',               // Location to redirect after changing email
        ],
        'send_verify' => [
            'after_verify_page' => '/',         // Location to redirect after triggering verification
        ],
    ],
    // Definition files to be loaded
    'definition_files' => [
        '/vendor/avoutic/web-framework/definitions/web_framework_definitions.php',
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
            'email-verification-link' => 'email-verification-link',
            'change-email-verification-link' => 'change-email-verification-link',
            'password-reset' => 'password-reset',
            'new-password' => 'new-password',
        ],
    ],
    'console_tasks' => [],                      /* Associative array of console tasks to register
                                                 * The key is the command name and the value is the class name
                                                 * of the task. The class must be a subclass of ConsoleTask.
                                                 */
];
