<?php

namespace WebFramework\Core;

use Postmark\PostmarkClient;

class PostmarkClientFactory
{
    private ?PostmarkClient $client = null;

    public function __construct(
        private string $api_key,
    ) {
    }

    public function get_client(): PostmarkClient
    {
        if ($this->client === null)
        {
            $this->client = new PostmarkClient($this->api_key);
        }

        return $this->client;
    }
}
