<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

/**
 * Class ConfigService.
 *
 * Provides access to configuration values for the WebFramework.
 */
class ConfigService
{
    /**
     * ConfigService constructor.
     *
     * @param array<string, mixed> $config The configuration array
     */
    public function __construct(
        private array $config,
    ) {}

    /**
     * Get a configuration value by its location.
     *
     * @param string $location The dot-notation location of the configuration value
     *
     * @return mixed The configuration value
     *
     * @throws \InvalidArgumentException If the configuration value is not found
     */
    public function get(string $location = ''): mixed
    {
        if (!strlen($location))
        {
            return $this->config;
        }

        $path = explode('.', $location);
        $part = $this->config;

        foreach ($path as $step)
        {
            if (!isset($part[$step]))
            {
                throw new \InvalidArgumentException("Missing configuration {$location}");
            }

            $part = $part[$step];
        }

        return $part;
    }
}
