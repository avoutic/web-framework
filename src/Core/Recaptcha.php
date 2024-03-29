<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;

class Recaptcha
{
    /**
     * @var array<string>
     */
    private array $errorCodes = [];

    public function __construct(
        private Client $client,
        private string $secretKey,
    ) {
    }

    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * @return array<string>
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    public function verifyResponse(string $recaptchaResponse): bool
    {
        if (!strlen($recaptchaResponse))
        {
            return false;
        }

        $recaptchaSecret = $this->secretKey;
        $this->errorCodes = [];

        $recaptchaData = [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
        ];

        $response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => $recaptchaData,
        ]);

        $body = $response->getBody();
        $body = json_decode($body, true);

        if (isset($body['error_codes']))
        {
            $this->errorCodes = $body['error_codes'];

            if (in_array('invalid-input-secret', $body['error-codes']))
            {
                throw new \RuntimeException('Invalid reCAPTCHA input secret used');
            }

            if (in_array('invalid-keys-secret', $body['error-codes']))
            {
                throw new \RuntimeException('Invalid reCAPTCHA key used');
            }
        }

        return $body['success'];
    }
}
