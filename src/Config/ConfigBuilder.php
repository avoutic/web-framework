<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Config;

/**
 * Class ConfigBuilder.
 *
 * Builds and manages configuration for the WebFramework.
 */
class ConfigBuilder
{
    /** @var array<string, mixed> The global configuration array */
    private array $globalConfig = [];

    /**
     * ConfigBuilder constructor.
     *
     * @param string $appDir The application directory path
     */
    public function __construct(
        private string $appDir,
    ) {}

    /**
     * Merge a configuration array on top of the existing global configuration.
     *
     * For numerically indexed arrays (lists), the array is fully replaced.
     * For associative arrays, keys are merged recursively.
     *
     * @param array<string, mixed> $config The configuration array to merge
     */
    public function mergeConfigOnTop(array $config): void
    {
        $this->globalConfig = $this->deepMerge($this->globalConfig, $config);
    }

    /**
     * Load a configuration file and return its contents as an array.
     *
     * @param string $configLocation The location of the configuration file relative to the app directory
     *
     * @return array<string, mixed> The configuration array from the file
     *
     * @throws \RuntimeException If the file does not contain a valid configuration array
     */
    private function loadConfigFile(string $configLocation): array
    {
        if ($configLocation[0] == '?')
        {
            $configLocation = substr($configLocation, 1);

            if (!file_exists("{$this->appDir}{$configLocation}"))
            {
                return [];
            }
        }

        if (!file_exists("{$this->appDir}{$configLocation}"))
        {
            throw new \RuntimeException("File '{$configLocation}' does not exist");
        }

        $fileConfig = require "{$this->appDir}{$configLocation}";
        if (!is_array($fileConfig))
        {
            throw new \RuntimeException("No valid config array found in '{$configLocation}'");
        }

        return $fileConfig;
    }

    /**
     * Build the configuration by merging multiple configuration files.
     *
     * @param array<string> $configs Config files to merge on top of each other in order.
     *                               File locations should be relative to the app dir
     *                               including leading /. If it starts with a '?' the file
     *                               does not have to be present.
     *
     * @return array<string, mixed> The final merged configuration array
     */
    public function buildConfig(array $configs): array
    {
        foreach ($configs as $configLocation)
        {
            $fileConfig = $this->loadConfigFile($configLocation);

            $this->mergeConfigOnTop($fileConfig);
        }

        return $this->globalConfig;
    }

    /**
     * Get the current global configuration.
     *
     * @return array<string, mixed> The current global configuration array
     */
    public function getConfig(): array
    {
        return $this->globalConfig;
    }

    /**
     * Get a flattened version of the current global configuration.
     *
     * @return array<string, mixed> The flattened configuration array
     */
    public function getFlattenedConfig(): array
    {
        return $this->flatten($this->globalConfig);
    }

    /**
     * Flatten a multi-dimensional array into a single-dimensional array with dot notation keys.
     *
     * @param array<string, mixed> $array  The array to flatten
     * @param string               $prefix The prefix to use for the flattened keys
     *
     * @return array<string, mixed> The flattened array
     */
    private function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value)
        {
            if (is_array($value))
            {
                $result += $this->flatten($value, $prefix.$key.'.');
            }
            else
            {
                $result[$prefix.$key] = $value;
            }
        }

        return $result;
    }

    /**
     * Deep merge $right on top of $left.
     * - If both values are arrays:
     *   - If either is a list (numerically indexed), replace entirely with $right.
     *   - Else merge associative keys recursively.
     * - Otherwise, return $right.
     */
    private function deepMerge(mixed $left, mixed $right): mixed
    {
        if (!is_array($left) || !is_array($right))
        {
            return $right;
        }

        $leftIsList = array_is_list($left);
        $rightIsList = array_is_list($right);

        if ($leftIsList && $rightIsList)
        {
            return $right;
        }

        $result = $left;
        foreach ($right as $key => $value)
        {
            if (array_key_exists($key, $result))
            {
                $result[$key] = $this->deepMerge($result[$key], $value);
            }
            else
            {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
