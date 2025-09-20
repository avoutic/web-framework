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
use WebFramework\Core\RuntimeEnvironment;

/**
 * Class RequiredAuth.
 *
 * Performs sanity checks related to required authentication files.
 */
class RequiredAuth extends SanityCheckBase
{
    /**
     * RequiredAuth constructor.
     *
     * @param LoggerInterface    $logger             The logger
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment service
     */
    public function __construct(
        private LoggerInterface $logger,
        private RuntimeEnvironment $runtimeEnvironment,
    ) {}

    /**
     * Perform checks for required authentication files.
     *
     * @return bool True if all checks pass, false otherwise
     */
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

            $this->logger->emergency('Required auth file not present', ['filename' => $filename]);

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
