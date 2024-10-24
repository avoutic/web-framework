<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use WebFramework\Core\RuntimeEnvironment;

/**
 * Class ConfigService.
 *
 * Handles configuration related to authentication configuration files.
 */
class ConfigService
{
    /**
     * ConfigService constructor.
     *
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment
     * @param string             $authDir            The directory containing auth configuration files
     */
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
        private string $authDir,
    ) {}

    /**
     * Get the authentication configuration for a specific name.
     *
     * @param string $name The name of the auth configuration to retrieve
     *
     * @return mixed The auth configuration
     *
     * @throws \RuntimeException If the configuration is invalid or doesn't exist
     */
    public function getAuthConfig(string $name): mixed
    {
        $authConfig = $this->loadFile($name);

        if (!is_array($authConfig) && !strlen($authConfig))
        {
            throw new \RuntimeException('Auth Config '.$name.' invalid');
        }

        return $authConfig;
    }

    /**
     * Load a configuration file.
     *
     * @param string $name The name of the configuration file to load
     *
     * @return array<mixed>|string The loaded configuration
     *
     * @throws \RuntimeException If the configuration file doesn't exist
     */
    private function loadFile(string $name): array|string
    {
        $filename = "{$this->runtimeEnvironment->getAppDir()}{$this->authDir}/{$name}.php";

        if (!file_exists($filename))
        {
            throw new \RuntimeException("Auth Config {$name} does not exist");
        }

        return require $filename;
    }
}
