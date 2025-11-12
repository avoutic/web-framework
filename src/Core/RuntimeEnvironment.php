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
 * Class RuntimeEnvironment.
 *
 * Represents the runtime environment configuration for the application.
 *
 * @codeCoverageIgnore
 */
class RuntimeEnvironment
{
    /**
     * RuntimeEnvironment constructor.
     *
     * @param string $appDir      The application directory path
     * @param string $baseUrl     The base URL of the application
     * @param bool   $debug       Whether debug mode is enabled
     * @param string $httpMode    The HTTP mode (e.g., 'http' or 'https')
     * @param bool   $offlineMode Whether offline mode is enabled
     * @param bool   $production  Whether the application is running in production mode
     * @param string $serverName  The server name
     */
    public function __construct(
        private string $appDir,
        private string $baseUrl,
        private bool $debug,
        private string $httpMode,
        private bool $offlineMode,
        private bool $production,
        private string $serverName,
    ) {}

    /**
     * Get the application directory path.
     *
     * @return string The application directory path
     */
    public function getAppDir(): string
    {
        return $this->appDir;
    }

    /**
     * Get the base URL of the application.
     *
     * @return string The base URL
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * Check if debug mode is enabled.
     *
     * @return bool True if debug mode is enabled, false otherwise
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * Get the HTTP mode.
     *
     * @return string The HTTP mode (e.g., 'http' or 'https')
     */
    public function getHttpMode(): string
    {
        return $this->httpMode;
    }

    /**
     * Check if offline mode is enabled.
     *
     * @return bool True if offline mode is enabled, false otherwise
     */
    public function isOfflineMode(): bool
    {
        return $this->offlineMode;
    }

    /**
     * Check if the application is running in production mode.
     *
     * @return bool True if in production mode, false otherwise
     */
    public function isProduction(): bool
    {
        return $this->production;
    }

    /**
     * Get the server name.
     *
     * @return string The server name
     */
    public function getServerName(): string
    {
        return $this->serverName;
    }
}
