<?php

namespace WebFramework\Core;

class RecaptchaFactory
{
    public function __construct(
        private GuzzleClientFactory $guzzleClientFactory,
        private string $secretKey,
    ) {
    }

    public function getRecaptcha(): Recaptcha
    {
        return new Recaptcha(
            $this->guzzleClientFactory->getClient(),
            $this->secretKey,
        );
    }
}
