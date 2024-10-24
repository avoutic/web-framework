<?php

namespace WebFramework\Core;

use Postmark\PostmarkClient;

/**
 * Factory class for creating Postmark API clients.
 */
class PostmarkClientFactory
{
    /** @var null|PostmarkClient The cached Postmark client instance */
    private ?PostmarkClient $client = null;

    /**
     * PostmarkClientFactory constructor.
     *
     * @param string $apiKey The Postmark API key
     */
    public function __construct(
        private string $apiKey,
    ) {}

    /**
     * Get a Postmark client instance.
     *
     * This method caches the client instance for reuse.
     *
     * @return PostmarkClient The Postmark client
     */
    public function getClient(): PostmarkClient
    {
        if ($this->client === null)
        {
            $this->client = new PostmarkClient($this->apiKey, timeout: 5);
        }

        return $this->client;
    }
}
