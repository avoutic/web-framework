<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use Psr\Log\LoggerInterface;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Exception\MissingAuthConfigException;

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
     * @param LoggerInterface    $logger             The logger
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment
     * @param string             $authDir            The directory containing auth configuration files
     */
    public function __construct(
        private LoggerInterface $logger,
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
     * @throws MissingAuthConfigException If the configuration file doesn't exist
     * @throws \RuntimeException          If the configuration is invalid
     */
    public function getAuthConfig(string $name): mixed
    {
        $authConfig = $this->loadFile($name);

        if (!is_array($authConfig) && !strlen($authConfig))
        {
            $this->logger->critical('Auth Config '.$name.' invalid', ['auth_config' => $authConfig]);

            throw new \RuntimeException('Auth Config '.$name.' invalid');
        }

        return $authConfig;
    }

    /**
     * Check if an auth configuration file exists.
     *
     * Use this for an integration that is optional, so its definition can leave the
     * integration out when it is not configured.
     *
     * @param string $name The name of the auth configuration
     */
    public function hasAuthConfig(string $name): bool
    {
        return file_exists($this->getFilename($name));
    }

    /**
     * Get an auth configuration, or a default when its file does not exist.
     *
     * A file that exists but has no usable content still throws: that is a mistake, not
     * an integration that is left out.
     *
     * @param string $name    The name of the auth configuration
     * @param mixed  $default The value to return when the file does not exist
     *
     * @return mixed The auth configuration, or the default
     *
     * @throws \RuntimeException If the auth configuration is invalid
     */
    public function getAuthConfigOrDefault(string $name, mixed $default): mixed
    {
        if (!$this->hasAuthConfig($name))
        {
            return $default;
        }

        return $this->getAuthConfig($name);
    }

    private function getFilename(string $name): string
    {
        return "{$this->runtimeEnvironment->getAppDir()}{$this->authDir}/{$name}.php";
    }

    /**
     * Load a configuration file.
     *
     * @param string $name The name of the configuration file to load
     *
     * @return array<mixed>|string The loaded configuration
     *
     * @throws MissingAuthConfigException If the configuration file doesn't exist
     */
    private function loadFile(string $name): array|string
    {
        if (!$this->hasAuthConfig($name))
        {
            throw new MissingAuthConfigException($name);
        }

        // In a scope of its own: a file that is required can change the variables of the
        // code that requires it
        //
        return (static function (string $authConfigFilename): mixed {
            return require $authConfigFilename;
        })($this->getFilename($name));
    }
}
