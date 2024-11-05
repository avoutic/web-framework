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
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Class Webhook.
 *
 * Handles sending webhook notifications to external services.
 */
class Webhook
{
    /**
     * Webhook constructor.
     *
     * @param Client $client The HTTP client for making requests
     * @param string $url    The URL to send webhook notifications to
     */
    public function __construct(
        private Client $client,
        private string $url,
    ) {}

    /**
     * Trigger a webhook notification.
     *
     * @param array<mixed> $data The data to send in the webhook payload
     *
     * @return Response The response from the webhook endpoint
     *
     * @throws GuzzleException   If there's an error sending the request
     * @throws \RuntimeException If the webhook data cannot be encoded
     */
    public function trigger(array $data): Response
    {
        $jsonEncodedData = json_encode($data);

        if ($jsonEncodedData === false)
        {
            throw new \RuntimeException('Failed to encode webhook data');
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
        catch (RequestException $e)
        {
            throw $e;
        }

        return $response;
    }
}
