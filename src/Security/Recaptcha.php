<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

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
    private ?float $score = null;
    private ?string $action = null;

    /**
     * Recaptcha constructor.
     *
     * @param LoggerInterface $logger    The logger
     * @param Client          $client    The HTTP client for making API requests
     * @param string          $secretKey The reCAPTCHA secret key
     */
    public function __construct(
        private LoggerInterface $logger,
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
     * Get the score of the last verification.
     *
     * reCAPTCHA v3 gives a score between 0.0 (very likely a bot) and 1.0 (very likely a
     * person). Which score is good enough is up to you.
     *
     * @return null|float The score, or null if the last verification gave none
     */
    public function getScore(): ?float
    {
        return $this->score;
    }

    /**
     * Get the action of the last verification.
     *
     * reCAPTCHA v3 gives the action the token was made for. Check that it is the action
     * you expect, so a token of one form cannot be used for another.
     *
     * @return null|string The action, or null if the last verification gave none
     */
    public function getAction(): ?string
    {
        return $this->action;
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
        $this->score = null;
        $this->action = null;

        $recaptchaData = [
            'secret' => $recaptchaSecret,
            'response' => $recaptchaResponse,
        ];

        $response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => $recaptchaData,
        ]);

        $body = $response->getBody();
        $body = json_decode((string) $body, true);

        // An answer that cannot be read is not a verification
        //
        if (!is_array($body))
        {
            $this->logger->debug('Recaptcha answer is not readable');

            return false;
        }

        if (isset($body['error-codes']))
        {
            $this->logger->debug('Recaptcha error', ['error_codes' => $body['error-codes']]);

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

        if (isset($body['score']) && is_numeric($body['score']))
        {
            $this->score = (float) $body['score'];
        }

        if (isset($body['action']) && is_string($body['action']))
        {
            $this->action = $body['action'];
        }

        return ($body['success'] ?? false) === true;
    }
}
