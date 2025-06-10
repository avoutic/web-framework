<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class EnvLoader.
 *
 * Loads environment variables from .env files.
 */
class EnvLoader
{
    /**
     * Load environment variables from a .env file.
     *
     * @param string $envFilePath The path to the .env file
     */
    public function loadEnvFile(string $envFilePath): void
    {
        if (!file_exists($envFilePath))
        {
            return;
        }

        $lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false)
        {
            return;
        }

        foreach ($lines as $line)
        {
            $line = trim($line);

            if (empty($line) || str_starts_with($line, '#'))
            {
                continue;
            }

            if (str_contains($line, '='))
            {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'")))
                {
                    $value = substr($value, 1, -1);
                }

                if (getenv($key) === false)
                {
                    putenv("{$key}={$value}");
                }
            }
        }
    }
}
