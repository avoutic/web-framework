<?php

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
