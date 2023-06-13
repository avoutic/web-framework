<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;

class GuzzleClientFactory
{
    public function getClient(): Client
    {
        return new Client();
    }
}
