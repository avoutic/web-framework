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

use GuzzleHttp\Client;

/**
 * Factory class for creating Guzzle HTTP clients.
 */
class GuzzleClientFactory
{
    /**
     * Get a new Guzzle HTTP client instance.
     *
     * @return Client The Guzzle HTTP client
     */
    public function getClient(): Client
    {
        return new Client();
    }
}
