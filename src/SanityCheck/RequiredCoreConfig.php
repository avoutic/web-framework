<?php

namespace WebFramework\SanityCheck;

use WebFramework\Core\ConfigService;

class RequiredCoreConfig extends Base
{
    public function __construct(
        private ConfigService $configService,
    ) {
    }

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
