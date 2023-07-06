<?php

namespace WebFramework\SanityCheck;

class RequiredAuth extends Base
{
    /**
     * @param array<string> $requiredAuths
     */
    public function __construct(
        private string $appDir,
        private array $requiredAuths,
    ) {
    }

    public function performChecks(): bool
    {
        // Check if all required auth files are present
        //
        $this->addOutput('Checking for auths:'.PHP_EOL);

        $error = false;

        foreach ($this->requiredAuths as $filename)
        {
            $path = "{$this->appDir}/config/auth/{$filename}";

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
