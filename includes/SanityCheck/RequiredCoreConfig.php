<?php

namespace WebFramework\Core\SanityCheck;

use WebFramework\Core\ConfigService;

class RequiredCoreConfig extends Base
{
    public function __construct(
        private ConfigService $config_service,
    ) {
    }

    public function perform_checks(): bool
    {
        // Check if all required config options files are present
        //
        $error = false;

        $this->add_output('Checking for sender_core.default_sender:'.PHP_EOL);

        if (strlen($this->config_service->get('sender_core.default_sender')))
        {
            $this->add_output(' - present'.PHP_EOL);
        }
        else
        {
            $error = true;
            $this->add_output(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->add_output('Checking for sender_core.assert_recipient:'.PHP_EOL);

        if (strlen($this->config_service->get('sender_core.assert_recipient')))
        {
            $this->add_output(' - present'.PHP_EOL);
        }
        else
        {
            $error = true;
            $this->add_output(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->add_output('Checking for valid security.hmac_key:'.PHP_EOL);

        if (strlen($this->config_service->get('security.hmac_key')) >= 20)
        {
            $this->add_output(' - present'.PHP_EOL);
        }
        else
        {
            $error = true;
            $this->add_output(' - not present. Not fixing.'.PHP_EOL);
        }

        $this->add_output('Checking for valid security.crypt_key:'.PHP_EOL);

        if (strlen($this->config_service->get('security.crypt_key')) >= 20)
        {
            $this->add_output(' - present'.PHP_EOL);
        }
        else
        {
            $error = true;
            $this->add_output(' - not present. Not fixing.'.PHP_EOL);
        }

        if (!$error)
        {
            $this->add_output('  Pass'.PHP_EOL.PHP_EOL);
        }
        else
        {
            $this->add_output(PHP_EOL.'Breaking off');

            return false;
        }

        return true;
    }
}
