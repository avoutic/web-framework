<?php

namespace WebFramework\Core;

// Default configuration
//
return [
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
    'databases' => [],                  /* list of extra database tags to load.
                                         * files will be retrieved from 'includes/db_config.{TAG}.php'
                                         */
    'versions' => [
        'supported_framework' => -1,    /* Default is always -1. App should set supported semantic
                                         * version of this framework it supports in own config.
                                         */
        'required_app_db' => 1,         /* Default is always 1. App should set this if it tracks its
                                         * own database version in the db.app_db_version config value
                                         * in the database and wants the framework to indicate
                                         * a mismatch between required and current value
                                         */
    ],
    'site_name' => 'Unknown',
    'http_mode' => 'https',
    'base_url' => '',                   // Add a base_url to be used in external urls
    'cache_enabled' => false,
    'auth_mode' => 'redirect',          // redirect, www-authenticate, custom (requires auth_module)
    'auth_module' => '',                // class name with full namespace
    /* Associative array with application sanity check class names with
     * full namespace as key and their config array as the value
     */
    'sanity_check_modules' => [],
    'authenticator' => [
        'unique_identifier' => 'email',
        'auth_required_message' => 'Authentication required. Please login.',
        'session_timeout' => 900,
        'user_class' => User::class,
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
    'route_files' => [],                // The files in 'routes' to explicitly load
    'sender_core' => [
        'default_sender' => '',
        'assert_recipient' => '',
    ],
];
