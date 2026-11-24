<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Migration;

/**
 * Class MigrationFileLoader.
 *
 * Loads a migration or scheme file. A file that is required runs in the scope of the code
 * that requires it, and can overwrite the variables of that code. So the file is required
 * in a scope of its own, without an object and without variables that matter.
 */
class MigrationFileLoader
{
    /**
     * Load a file and return the array it returns.
     *
     * @param string $path The path of the file
     *
     * @return array<mixed> The array the file returns
     *
     * @throws \RuntimeException If the file does not return an array
     */
    public function load(string $path): array
    {
        // A static function with the path as its only variable. The file can change that
        // variable, but it is not used after the file is loaded
        //
        $data = (static function (string $migrationFileLoaderPath): mixed {
            return require $migrationFileLoaderPath;
        })($path);

        if (!is_array($data))
        {
            throw new \RuntimeException("File {$path} does not return an array");
        }

        return $data;
    }
}
