<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Console;

/**
 * Class EntryPointPublisher.
 *
 * Copies an entry point of the framework, such as public/index.php, into the application.
 * Every failure is an exception, so a missing or moved source does not leave a stale file
 * in place unnoticed.
 */
class EntryPointPublisher
{
    /**
     * Publish a file.
     *
     * @param string $source The file in the framework package
     * @param string $target The location in the application
     *
     * @return string The location the file was published to
     *
     * @throws \RuntimeException If the file cannot be published
     */
    public function publish(string $source, string $target): string
    {
        if (!is_file($source))
        {
            throw new \RuntimeException("Cannot publish: source file '{$source}' does not exist");
        }

        $directory = dirname($target);

        if (!is_dir($directory) && !@mkdir($directory, 0o755, true) && !is_dir($directory))
        {
            throw new \RuntimeException("Cannot publish: directory '{$directory}' cannot be created");
        }

        if (!@copy($source, $target))
        {
            $reason = error_get_last()['message'] ?? 'unknown error';

            throw new \RuntimeException("Cannot publish '{$source}' to '{$target}': {$reason}");
        }

        return $target;
    }
}
