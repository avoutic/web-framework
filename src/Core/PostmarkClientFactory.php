<?php

namespace WebFramework\Core;

use Postmark\PostmarkClient;

class PostmarkClientFactory
{
    private ?PostmarkClient $client = null;

    public function __construct(
        private string $apiKey,
    ) {
    }

    public function getClient(): PostmarkClient
    {
        if ($this->client === null)
        {
            $this->client = new PostmarkClient($this->apiKey, timeout: 5);
        }

        return $this->client;
    }
}
