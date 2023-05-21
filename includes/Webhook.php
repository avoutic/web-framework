<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface as Response;

class Webhook
{
    public function __construct(
        protected Client $client,
        protected string $url,
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public function trigger(array $data): Response
    {
        $json_encoded_data = json_encode($data);
        if ($json_encoded_data === false)
        {
            throw new \RuntimeException('Failed to encode data');
        }

        try
        {
            $response = $this->client->post($this->url, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $json_encoded_data,
            ]);
        }
        catch (\GuzzleHttp\Exception\RequestException $e)
        {
            throw $e;
        }

        return $response;
    }
}
