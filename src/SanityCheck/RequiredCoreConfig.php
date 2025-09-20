<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\SanityCheck;

use Psr\Log\LoggerInterface;
use WebFramework\Config\ConfigService;

/**
 * Class RequiredCoreConfig.
 *
 * Performs sanity checks related to required core configuration options.
 */
class RequiredCoreConfig extends SanityCheckBase
{
    /**
     * RequiredCoreConfig constructor.
     *
     * @param ConfigService $configService The configuration service
     */
    public function __construct(
        private ConfigService $configService,
        private LoggerInterface $logger,
    ) {}

    /**
     * Perform checks for required core configuration options.
     *
     * @return bool True if all checks pass, false otherwise
     */
    public function performChecks(): bool
    {
        // Check if all required config options files are present
        //
        $error = false;

        $this->addOutput('Checking for sender_core.default_sender:'.PHP_EOL);

        if (strlen($this->configService->get('sender_core.default_sender')))
        {
            $this->addOutput(' - present'.PHP_EOL);
        }
        else
        {
            $this->logger->emergency('Required core config option sender_core.default_sender not present');

            $error = true;
            $this->addOutput(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->addOutput('Checking for sender_core.assert_recipient:'.PHP_EOL);

        if (strlen($this->configService->get('sender_core.assert_recipient')))
        {
            $this->addOutput(' - present'.PHP_EOL);
        }
        else
        {
            $this->logger->emergency('Required core config option sender_core.assert_recipient not present');

            $error = true;
            $this->addOutput(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->addOutput('Checking for valid security.hmac_key:'.PHP_EOL);

        if (strlen($this->configService->get('security.hmac_key')) >= 20)
        {
            $this->addOutput(' - present'.PHP_EOL);
        }
        else
        {
            $this->logger->emergency('Required core config option security.hmac_key not present');

            $error = true;
            $this->addOutput(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->addOutput('Checking for valid security.crypt_key:'.PHP_EOL);

        if (strlen($this->configService->get('security.crypt_key')) >= 20)
        {
            $this->addOutput(' - present'.PHP_EOL);
        }
        else
        {
            $this->logger->emergency('Required core config option security.crypt_key not present');

            $error = true;
            $this->addOutput(' - not present. Not fixing.'.PHP_EOL);
        }

        if (!$error)
        {
            $this->addOutput('  Pass'.PHP_EOL.PHP_EOL);
        }
        else
        {
            $this->addOutput(PHP_EOL.'Breaking off');

            return false;
        }

        return true;
    }
}
