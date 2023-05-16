<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;

class Recaptcha
{
    /**
     * @var array<string>
     */
    protected array $error_codes = [];

    public function __construct(
        protected AssertService $assert_service,
        protected Client $client,
        protected string $secret_key,
    ) {
    }

    public function set_secret_key(string $secret_key): void
    {
        $this->secret_key = $secret_key;
    }

    /**
     * @return array<string>
     */
    public function get_error_codes(): array
    {
        return $this->error_codes;
    }

    public function verify_response(string $recaptcha_response): bool
    {
        if (!strlen($recaptcha_response))
        {
            return false;
        }

        $recaptcha_secret = $this->secret_key;
        $this->error_codes = [];

        $recaptcha_data = [
            'secret' => $recaptcha_secret,
            'response' => $recaptcha_response,
        ];

        $response = $this->client->post('https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => $recaptcha_data,
        ]);

        $body = $response->getBody();
        $body = json_decode($body, true);

        if (isset($body['error_codes']))
        {
            $this->error_codes = $body['error_codes'];

            $this->assert_service->verify(!in_array('invalid-input-secret', $body['error-codes']), 'Invalid reCAPTCHA input secret used');
            $this->assert_service->verify(!in_array('invalid-keys-secret', $body['error-codes']), 'Invalid reCAPTCHA key used');
        }

        return $body['success'];
    }
}
