<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;

/**
 * Class Recaptcha.
 *
 * Handles reCAPTCHA verification using Google's reCAPTCHA API.
 */
class Recaptcha
{
    /**
     * @var array<string> Array to store error codes returned by the reCAPTCHA API
     */
    private array $errorCodes = [];

    /**
     * Recaptcha constructor.
     *
     * @param Client $client    The HTTP client for making API requests
     * @param string $secretKey The reCAPTCHA secret key
     */
    public function __construct(
        private Client $client,
        private string $secretKey,
    ) {}

    /**
     * Set the reCAPTCHA secret key.
     *
     * @param string $secretKey The new secret key
     */
    public function setSecretKey(string $secretKey): void
    {
        $this->secretKey = $secretKey;
    }

    /**
     * Get the error codes from the last verification attempt.
     *
     * @return array<string> Array of error codes
     */
    public function getErrorCodes(): array
    {
        return $this->errorCodes;
    }

    /**
     * Verify the reCAPTCHA response.
     *
     * @param string $recaptchaResponse The reCAPTCHA response string to verify
     *
     * @return bool True if verification succeeds, false otherwise
     *
     * @throws \RuntimeException If there's an error with the reCAPTCHA configuration
     */
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

        if (isset($body['error-codes']))
        {
            $this->errorCodes = $body['error-codes'];

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
