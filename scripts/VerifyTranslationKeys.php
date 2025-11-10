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

/**
 * This script verifies that all translation strings used in actions and src directories
 * are present in translations/en.php.
 */
$translationsPath = __DIR__.'/../translations/en.php';
$srcDir = __DIR__.'/../src';
$actionsDir = __DIR__.'/../actions';

if (!file_exists($translationsPath))
{
    echo "Error: translations/en.php not found at {$translationsPath}\n";

    exit(1);
}

// Load and parse translations/en.php
$translations = require $translationsPath;

if (!is_array($translations))
{
    echo "Error: translations/en.php does not return an array\n";

    exit(1);
}

/**
 * Flatten a nested array into dot-notation keys.
 *
 * @param array<string, mixed> $array
 *
 * @return array<string>
 */
function flattenTranslationKeys(array $array, string $prefix = ''): array
{
    $keys = [];

    foreach ($array as $key => $value)
    {
        $fullKey = $prefix === '' ? $key : "{$prefix}.{$key}";

        if (is_array($value) && !empty($value) && array_is_list($value) === false)
        {
            // Recursively flatten nested associative arrays
            $keys = array_merge($keys, flattenTranslationKeys($value, $fullKey));
        }
        else
        {
            // Add the key itself
            $keys[] = $fullKey;
        }
    }

    return $keys;
}

// Get all available translation keys from translations/en.php
$availableKeys = flattenTranslationKeys($translations);
$availableKeysSet = array_flip($availableKeys);

/**
 * Extract translation keys from PHP code.
 *
 * @return array<string>
 */
function extractTranslationKeys(string $content): array
{
    $keys = [];

    // Pattern 1: messageService->add('error', 'category.tag')
    // Matches: messageService->add('error', 'category.tag') or $this->messageService->add('error', 'category.tag')
    preg_match_all("/(?:\\\$this->|\\\$)?messageService->add\\s*\\(\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $matches);
    if (!empty($matches[1]))
    {
        $keys = array_merge($keys, $matches[1]);
    }

    // Pattern 2: messageService->add('error', 'category.tag', 'category.tag_extra')
    // Matches the extra message parameter
    preg_match_all("/(?:\\\$this->|\\\$)?messageService->add\\s*\\(\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $extraMatches);
    if (!empty($extraMatches[1]))
    {
        $keys = array_merge($keys, $extraMatches[1]);
    }

    // Pattern 3: responseEmitter->buildRedirect(..., 'error', 'category.tag')
    // Matches: responseEmitter->buildRedirect(..., 'error', 'category.tag')
    preg_match_all("/(?:\\\$this->|\\\$)?responseEmitter->buildRedirect\\s*\\([^)]*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $redirectMatches);
    if (!empty($redirectMatches[1]))
    {
        $keys = array_merge($keys, $redirectMatches[1]);
    }

    // Pattern 4: responseEmitter->buildRedirect(..., 'error', 'category.tag', 'category.tag_extra')
    // Matches the extra message parameter in buildRedirect
    preg_match_all("/(?:\\\$this->|\\\$)?responseEmitter->buildRedirect\\s*\\([^)]*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $redirectExtraMatches);
    if (!empty($redirectExtraMatches[1]))
    {
        $keys = array_merge($keys, $redirectExtraMatches[1]);
    }

    // Pattern 5: responseEmitter->buildQueryRedirect(..., 'error', 'category.tag')
    preg_match_all("/(?:\\\$this->|\\\$)?responseEmitter->buildQueryRedirect\\s*\\([^)]*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $queryRedirectMatches);
    if (!empty($queryRedirectMatches[1]))
    {
        $keys = array_merge($keys, $queryRedirectMatches[1]);
    }

    // Pattern 6: responseEmitter->buildQueryRedirect(..., 'error', 'category.tag', 'category.tag_extra')
    preg_match_all("/(?:\\\$this->|\\\$)?responseEmitter->buildQueryRedirect\\s*\\([^)]*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $queryRedirectExtraMatches);
    if (!empty($queryRedirectExtraMatches[1]))
    {
        $keys = array_merge($keys, $queryRedirectExtraMatches[1]);
    }

    // Pattern 7: String literals matching translation key pattern (category.tag)
    // Look for single or double quoted strings that match the pattern
    // This catches cases like: $message = 'category.tag' or throw new ValidationException('field', 'category.tag')
    // But exclude config keys (those used with configService->get())
    preg_match_all("/['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $literalMatches);
    if (!empty($literalMatches[1]))
    {
        foreach ($literalMatches[1] as $key)
        {
            // Filter out config keys - check if it's used with configService->get()
            // Config keys often have patterns like 'actions.login.location', 'error_handlers.403', etc.
            $isConfigKey = preg_match("/configService->get\\s*\\(\\s*['\"]".preg_quote($key, '/')."['\"]/", $content);
            if ($isConfigKey)
            {
                continue;
            }

            // Known translation categories: authenticator, change_email, change_password,
            // generic, login, register, reset_password, upload, validation, verify
            $knownCategories = ['authenticator', 'change_email', 'change_password', 'generic', 'login', 'register', 'reset_password', 'upload', 'validation', 'verify'];
            $parts = explode('.', $key);
            if (count($parts) === 2 && in_array($parts[0], $knownCategories, true))
            {
                $keys[] = $key;
            }
        }
    }

    // Pattern 8: ValidationException with translation keys
    // Matches: new ValidationException('field', 'category.tag')
    preg_match_all("/new\\s+ValidationException\\s*\\(\\s*['\"][^'\"]+['\"]\\s*,\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $validationMatches);
    if (!empty($validationMatches[1]))
    {
        $keys = array_merge($keys, $validationMatches[1]);
    }

    // Pattern 9: Variables assigned translation keys
    // Matches: $message = 'category.tag' or $errorMessage = 'category.tag'
    // But exclude config keys
    preg_match_all("/\\\$[a-zA-Z_][a-zA-Z0-9_]*\\s*=\\s*['\"]([a-z_]+\\.[a-z_]+)['\"]/", $content, $varMatches);
    if (!empty($varMatches[1]))
    {
        foreach ($varMatches[1] as $key)
        {
            // Filter out config keys
            $isConfigKey = preg_match("/configService->get\\s*\\(\\s*['\"]".preg_quote($key, '/')."['\"]/", $content);
            if ($isConfigKey)
            {
                continue;
            }

            $parts = explode('.', $key);
            if (count($parts) === 2)
            {
                $knownCategories = ['authenticator', 'change_email', 'change_password', 'generic', 'login', 'register', 'reset_password', 'upload', 'validation', 'verify'];
                if (in_array($parts[0], $knownCategories, true))
                {
                    $keys[] = $key;
                }
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
$allFiles = array_merge($srcFiles, $actionsFiles);

// Extract all used translation keys
$usedKeys = [];
$keyLocations = [];

foreach ($allFiles as $file)
{
    $content = file_get_contents($file);
    if ($content === false)
    {
        continue;
    }

    $fileKeys = extractTranslationKeys($content);

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

// Find missing keys (used but not defined)
$missingKeys = [];

foreach ($usedKeys as $key)
{
    if (!isset($availableKeysSet[$key]))
    {
        $missingKeys[] = $key;
    }
}

// Find unused keys (defined but not used)
$unusedKeys = [];

foreach ($availableKeys as $key)
{
    if (!isset($usedKeysSet[$key]))
    {
        // Check if this is an _extra key and if the base key is used
        if (str_ends_with($key, '_extra'))
        {
            $baseKey = substr($key, 0, -6); // Remove '_extra' suffix
            if (isset($usedKeysSet[$baseKey]))
            {
                // Base key is used, so _extra is considered used
                continue;
            }
        }

        $unusedKeys[] = $key;
    }
}

sort($unusedKeys);

// Output results
echo "Translation Key Verification Report\n";
echo str_repeat('=', 60)."\n\n";

echo 'Total translation keys found in code: '.count($usedKeys)."\n";
echo 'Total translation keys available in translations/en.php: '.count($availableKeys)."\n";
echo 'Missing keys (used but not defined): '.count($missingKeys)."\n";
echo 'Unused keys (defined but not used): '.count($unusedKeys)."\n\n";

$hasIssues = false;

if (count($missingKeys) > 0)
{
    $hasIssues = true;
    echo "MISSING TRANSLATION KEYS (used but not defined):\n";
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

if (count($unusedKeys) > 0)
{
    echo "UNUSED TRANSLATION KEYS (defined but not used):\n";
    echo str_repeat('-', 60)."\n";

    foreach ($unusedKeys as $key)
    {
        echo "  - {$key}\n";
    }

    echo "\n";
}

if ($hasIssues)
{
    exit(1);
}

if (count($unusedKeys) > 0)
{
    echo "✓ All used translation keys are present in translations/en.php\n";
    echo "⚠ Some translation keys in translations/en.php are unused\n";
}
else
{
    echo "✓ All translation keys are present in translations/en.php\n";
    echo "✓ All translation keys in translations/en.php are used\n";
}

exit(0);
