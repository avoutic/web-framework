#!/usr/bin/env php
<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This script verifies that all config keys used in actions and src directories
 * are present in config/base_config.php.
 */

// Define env() function if not already defined (for parsing base_config.php)
if (!function_exists('env'))
{
    function env(string $name, mixed $default = null): mixed
    {
        return $default;
    }
}

$baseConfigPath = __DIR__.'/../config/base_config.php';
$srcDir = __DIR__.'/../src';
$actionsDir = __DIR__.'/../actions';
$definitionsDir = __DIR__.'/../definitions';

if (!file_exists($baseConfigPath))
{
    echo "Error: config/base_config.php not found at {$baseConfigPath}\n";

    exit(1);
}

// Load and parse base_config.php
$baseConfig = require $baseConfigPath;

if (!is_array($baseConfig))
{
    echo "Error: config/base_config.php does not return an array\n";

    exit(1);
}

/**
 * Flatten a nested array into dot-notation keys.
 *
 * @param array<string, mixed> $array
 *
 * @return array<string>
 */
function flattenConfigKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value)
    {
        $fullKey = $prefix === '' ? $key : "{$prefix}.{$key}";

        if (is_array($value) && !empty($value) && array_is_list($value) === false)
        {
            // Add the parent key itself (it's a valid config key)
            $keys[] = $fullKey;
            // Recursively flatten nested associative arrays
            $keys = array_merge($keys, flattenConfigKeys($value, $fullKey));
        }
        else
        {
            // Add the key itself (even if it's an array, it's still a valid key)
            $keys[] = $fullKey;
        }
    }

    return $keys;
}

// Get all available config keys from base_config.php
$availableKeys = flattenConfigKeys($baseConfig);
$availableKeysSet = array_flip($availableKeys);

/**
 * Extract config keys from PHP code.
 *
 * @return array<string>
 */
function extractConfigKeys(string $content): array
{
    $keys = [];

    // Pattern 1: configService->get('key') or configService->get("key")
    // Matches: configService->get('...') or $this->configService->get("...") or configService->get('...')
    // Also handles: $configService->get('...')
    preg_match_all("/(?:\\\$this->|\\\$)?configService->get\\s*\\(\\s*['\"]([^'\"]+)['\"]\\s*\\)/", $content, $matches);
    if (!empty($matches[1]))
    {
        $keys = array_merge($keys, $matches[1]);
    }

    // Pattern 2: DI\get('key') - used in definitions files
    // Matches: DI\get('...') or DI\get("...")
    preg_match_all("/DI\\\\get\\s*\\(\\s*['\"]([^'\"]+)['\"]\\s*\\)/", $content, $diMatches);
    if (!empty($diMatches[1]))
    {
        foreach ($diMatches[1] as $key)
        {
            // Only include keys that look like config keys (have dots or are known top-level keys)
            // Skip container keys like 'app_dir', 'app_name', etc. that aren't config keys
            // But include config keys like 'authenticator.session_timeout', 'http_mode', etc.
            if (str_contains($key, '.') || preg_match('/^(debug|production|timezone|preload|http_mode|base_url|logging|authenticator|security|error_handlers|actions|definition_files|middlewares|routes|sender_core|translations|user_mailer|console_tasks|sanity_check_modules)$/', $key))
            {
                $keys[] = $key;
            }
        }
    }

    // Pattern 3: Handle match expressions that set config keys (used in Verify.php)
    // Look for match expressions that assign config keys to variables
    // Example: 'login' => 'actions.login.after_verify_page',
    // Only match keys that start with known config prefixes to avoid false positives
    $configPrefixes = ['actions', 'security', 'logging', 'authenticator', 'error_handlers', 'sender_core', 'translations', 'middlewares', 'routes', 'definition_files', 'console_tasks', 'user_mailer', 'sanity_check_modules'];
    preg_match_all("/(?:=>\\s*|=\\s*)['\"]((?:".implode('|', $configPrefixes).")\\.[^'\"]+)['\"]/i", $content, $matchKeys);
    if (!empty($matchKeys[1]))
    {
        foreach ($matchKeys[1] as $key)
        {
            // Verify it's a valid config key pattern
            if (preg_match('/^[a-z_][a-z0-9_.]*\.[a-z0-9_.]+$/i', $key))
            {
                $keys[] = $key;
            }
        }
    }

    return array_unique($keys);
}

/**
 * Recursively find all PHP files in a directory.
 *
 * @return array<string>
 */
function findPhpFiles(string $dir): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file)
    {
        if ($file->isFile() && $file->getExtension() === 'php')
        {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

// Find all PHP files
$srcFiles = is_dir($srcDir) ? findPhpFiles($srcDir) : [];
$actionsFiles = is_dir($actionsDir) ? findPhpFiles($actionsDir) : [];
$definitionsFiles = is_dir($definitionsDir) ? findPhpFiles($definitionsDir) : [];
$allFiles = array_merge($srcFiles, $actionsFiles, $definitionsFiles);

// Extract all used config keys
$usedKeys = [];
$keyLocations = [];

foreach ($allFiles as $file)
{
    $content = file_get_contents($file);
    if ($content === false)
    {
        continue;
    }

    $fileKeys = extractConfigKeys($content);

    foreach ($fileKeys as $key)
    {
        // Skip empty keys
        if ($key === '')
        {
            continue;
        }

        $usedKeys[$key] = true;

        if (!isset($keyLocations[$key]))
        {
            $keyLocations[$key] = [];
        }

        $relativePath = str_replace(__DIR__.'/../', '', $file);
        $keyLocations[$key][] = $relativePath;
    }
}

$usedKeys = array_keys($usedKeys);
sort($usedKeys);
$usedKeysSet = array_flip($usedKeys);

// Find missing keys
$missingKeys = [];

foreach ($usedKeys as $key)
{
    if (!isset($availableKeysSet[$key]))
    {
        $missingKeys[] = $key;
    }
}

// Output results
echo "Config Key Verification Report\n";
echo str_repeat('=', 60)."\n\n";

echo 'Total config keys found in code: '.count($usedKeys)."\n";
echo 'Total config keys available in config/base_config.php: '.count($availableKeys)."\n";
echo 'Missing keys: '.count($missingKeys)."\n\n";

$hasIssues = false;

if (count($missingKeys) > 0)
{
    $hasIssues = true;
    echo "MISSING CONFIG KEYS:\n";
    echo str_repeat('-', 60)."\n";

    foreach ($missingKeys as $key)
    {
        echo "  - {$key}\n";
        if (isset($keyLocations[$key]))
        {
            foreach ($keyLocations[$key] as $location)
            {
                echo "    Used in: {$location}\n";
            }
        }
    }

    echo "\n";
}

if ($hasIssues)
{
    exit(1);
}

echo "✓ All config keys are present in config/base_config.php\n";

exit(0);
