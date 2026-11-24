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

/**
 * Class SessionOptions.
 *
 * Builds the options for the PHP session of the application.
 */
class SessionOptions
{
    /**
     * Build the session options.
     *
     * The base options follow from the settings of the application. The configured options
     * are put on top: they can add PHP session settings, such as cookie_samesite or
     * save_path, and replace base options. An option that is configured as null is left out.
     *
     * @param string               $appName    The name of the application, used as the session name
     * @param int                  $lifetime   The lifetime of the session cookie in seconds
     * @param string               $hostName   The host name, used as the cookie domain
     * @param string               $httpMode   The HTTP mode of the application ('http' or 'https')
     * @param array<string, mixed> $configured The options from the configuration (session.options)
     *
     * @return array<string, mixed> The options for the session
     */
    public function build(string $appName, int $lifetime, string $hostName, string $httpMode, array $configured): array
    {
        $options = [
            'name' => $appName,
            'lifetime' => $lifetime,
            'path' => '/',
            'domain' => $hostName,
            'secure' => $httpMode === 'https',
            'httponly' => true,
        ];

        foreach ($configured as $key => $value)
        {
            if ($value === null)
            {
                unset($options[$key]);

                continue;
            }

            $options[$key] = $value;
        }

        return $options;
    }
}
