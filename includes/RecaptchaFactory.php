<?php

namespace WebFramework\Core;

class RecaptchaFactory
{
    public function __construct(
        private AssertService $assertService,
        private GuzzleClientFactory $guzzleClientFactory,
        private string $secretKey,
    ) {
    }

    public function getRecaptcha(): Recaptcha
    {
        return new Recaptcha(
            $this->assertService,
            $this->guzzleClientFactory->getClient(),
            $this->secretKey,
        );
    }
}
