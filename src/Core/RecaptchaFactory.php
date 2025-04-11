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

use Psr\Log\LoggerInterface;
use WebFramework\Support\GuzzleClientFactory;

/**
 * Class RecaptchaFactory.
 *
 * Factory class for creating Recaptcha instances.
 */
class RecaptchaFactory
{
    /**
     * RecaptchaFactory constructor.
     *
     * @param GuzzleClientFactory $guzzleClientFactory Factory for creating Guzzle HTTP clients
     * @param LoggerInterface     $logger              The logger
     * @param string              $secretKey           The reCAPTCHA secret key
     */
    public function __construct(
        private GuzzleClientFactory $guzzleClientFactory,
        private LoggerInterface $logger,
        private string $secretKey,
    ) {}

    /**
     * Get a new Recaptcha instance.
     *
     * @return Recaptcha A new Recaptcha instance
     */
    public function getRecaptcha(): Recaptcha
    {
        return new Recaptcha(
            $this->logger,
            $this->guzzleClientFactory->getClient(),
            $this->secretKey,
        );
    }
}
