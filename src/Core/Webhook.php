<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;

class Webhook
{
    public function __construct(
        private Client $client,
        private string $url,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public function trigger(array $data): Response
    {
        $jsonEncodedData = json_encode($data);
        if ($jsonEncodedData === false)
        {
            throw new \RuntimeException('Failed to encode data');
        }

        try
        {
            $response = $this->client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $jsonEncodedData,
            ]);
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {
            throw $e;
        }

        return $response;
    }
}
