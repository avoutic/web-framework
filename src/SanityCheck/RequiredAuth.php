<?php

namespace WebFramework\SanityCheck;

use WebFramework\Core\RuntimeEnvironment;

class RequiredAuth extends Base
{
    public function __construct(
        private RuntimeEnvironment $runtimeEnvironment,
    ) {
    }

    public function performChecks(): bool
    {
        // Check if all required auth files are present
        //
        $this->addOutput('Checking for auths:'.PHP_EOL);

        $error = false;

        foreach ($this->config as $filename)
        {
            $path = "{$this->runtimeEnvironment->getAppDir()}/config/auth/{$filename}";

            $exists = file_exists($path);

            if ($exists)
            {
                $this->addOutput(" - {$filename} present".PHP_EOL);

                continue;
            }

            $this->addOutput(" - {$filename} not present. Not fixing.".PHP_EOL);
            $error = true;
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
